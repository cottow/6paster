<?php
/**
6core.net paster

A tiny pastebin clone. I created this because I don't perse like all my pastes ending up
on a public server somewhere. This paster can be very quickly deployed on your own system
allowing you to keep control over your pastes. It is coded with security in mind, forces
the use of HTTPS, and imposes rate limits on posters.

*/

ob_start();

header("Strict-Transport-Security: max-age=31536000"); // 1 year

define('CONFIG', '../config.php');
define('TPLDIR','../tpl/');

$base = dirname($_SERVER['SCRIPT_NAME']);
if( $base != '/' )
{
	$base .= '/';
}

define('BASEURL', $base );

require(CONFIG);

$allowed_image_types = array( 
    IMAGETYPE_GIF, 
    IMAGETYPE_JPEG, 
    IMAGETYPE_PNG, 
    IMAGETYPE_BMP
);

function do_cleanup()
{
	global $dbh;
	
	$stmt = $dbh->prepare("DELETE FROM `pastes` WHERE `expires` < NOW()");
	$stmt->execute();
}

function check_setup()
{
	global $config;

	// check register_globals
	if( ini_get('register_globals') )
	{
		die('register_globals is enabled. I can\'t work like this.');
	}

	// check gpc_quotes
	if( get_magic_quotes_gpc() )
	{
		die('magic_quotes_gpc is enabled. I can\'t work like this.');
	}

	// check SSL
	if( !array_key_exists('HTTPS', $_SERVER) || $_SERVER['HTTPS'] != "on")
	{
		die('I really like encryption. Please use SSL.');
	} else {
		header("Strict-Transport-Security: max-age=15768000;");
	}
	
	// sane config?
	if( $config['limit_hour'] > $config['limit_day'] )
	{
		die('You should allow less pastes per hour than per day, silly');
	}

	// 
	if( empty($config['server_name']) )
	{
		$config['server_name'] = 'https://p.6core.net';
	}

	// htaccess installed?
	if( stristr($_SERVER['SERVER_SOFTWARE'], 'apache' ) && !file_exists('.htaccess'))
	{
		die('You should install the included .htaccess file in the same dir as index.php');
	}

    if( !function_exists( 'mysqli_connect' ) )
    {
        die('You do not have mysqli installed, quitting.');
    }

	return true;
}

function show_post( $ident )
{
	global $dbh;

	$stmt = $dbh->prepare("SELECT `text`,`mimetype` FROM `pastes` WHERE `ident` = ?");
	if( !$stmt )
	{
		die( 'mysql error' );
	}

	$stmt->bind_param('s', $ident );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $content, $mime_type );
    
	if( $stmt->num_rows == 1 )
	{
		$stmt->fetch();
        // make sure we don't put newlines in headers
        $mime_type = str_replace("\n", '', $mime_type );

        // fix the charset
        if( $mime_type == 'text/plain' )
        {
            // charset is hardcoded in the db
            $mime_type .= '; charset=utf-8';

            // need some special tricks for Internet Explorer
            if( preg_match("/MSIE (\d)/", $_SERVER['HTTP_USER_AGENT'], $matches) )
            {   
                // MS IE
                if( (int)$matches[1] < 8 ) 
                {   
                    // MS IE < 8 is retarded and will parse text as html even if 
                    // content-type says text/plain
                    $content = htmlspecialchars($content);
                } else {
                    // MS IE >= 8 is retarded and needs this header to prevent 
                    // parsing html in text/plain
                    header("X-Content-Type-Options: nosniff");
                }   
            }  
        } else {
            // yes, I'm positively paranoid
            if( strpos($mime_type,'image') !== 0 )
            {
                return;
            }
        }

		header("Content-Type: $mime_type");
        header("Vary: User-Agent");
		require(TPLDIR.'post.php');
		return;
	}

	// not found
	header("HTTP/1.0 404 Not Found", true, 404);
	require(TPLDIR.'404.php');
}

function show_form() 
{
	global $config;

	require(TPLDIR.'header.php');
	require(TPLDIR.'form.php');
	require( TPLDIR.'footer.php');
}

function do_paste()
{
	global $dbh;
	global $config;
    global $allowed_image_types;

	require(TPLDIR.'header.php');

	if( empty( $_POST['content'] ) || empty( $_POST['ttl'] ))
	{
		$errmsg = "You did not set all parameters";
		require( TPLDIR.'error.php');
		return;
	}

	if( strlen( $_POST['content']) > $config['paste_max_chars'] )
	{
		$errmsg = "Your paste exceeds the max limit of ".$config['paste_max_chars'];
		require( TPLDIR.'error.php');
		return;
	}

	$ttl = intval( $_POST['ttl'] );
	
	if( $ttl < $config['ttl_min'] )
	{
		$ttl = $config['ttl_min'];
	} else if( $ttl > $config['ttl_max'] ) {
		$ttl = $config['ttl_max'];
	}

	if( limit_exceeded() )
	{
		$errmsg = "You have reached your throttle limit, try again later.";
		require( TPLDIR.'error.php');
		return;
	}
   
    $mime_type = 'text/plain';

    // check if it's binary or a string
    if(!mb_detect_encoding($_POST['content']))
    {
        // binary. is it an image?
        $tmp_filename = tempnam(0, "6paster_");
        $fh = fopen( $tmp_filename, "w");
        fwrite( $fh, $_POST['content'] );
        fclose( $fh );

        $filetype = exif_imagetype( $tmp_filename );
        unlink( $tmp_filename );

        if( !$filetype || !in_array( $filetype, $allowed_image_types) )
        {
            $errmsg = "Sorry, this isn't a file sharing service. Filetype ".$filetype." not allowed.";
            require( TPLDIR.'error.php');
            return;
        }
        
        $mime_type = image_type_to_mime_type( $filetype );

    }
    ob_end_clean();

	// it's OK now, let's post it
	$ident = generate_ident();
	$stmt = $dbh->prepare("INSERT INTO `pastes` SET `ident`= ?, `ip`=?, `date`=NOW(), `text`=?, `mimetype`=?, `expires` = TIMESTAMPADD( SECOND, ?, NOW())");
	$stmt->bind_param('ssssi', $ident, $_SERVER['REMOTE_ADDR'], $_POST['content'], $mime_type, $ttl );
	$stmt->execute();
    
	header("Location: ".BASEURL."p/".$ident);

}

function generate_ident()
{
	global $dbh, $config;

	$exists = true;
	while( $exists )
	{
        // generate identifier. must have enough entropy (so rand()) but since we can't trust php's
        // rand, we hash it together with a site-specific secret. then to compress the url, we base64
        // encode instead of hex encode the result. Furthermore, + and / might give trouble in GET
        // parameters, so we replace those.
        $ident = substr( base64_encode( sha1 ( rand(0,10000000000) . $config['mysql_pass'], true ) ) , 0, 24 );
        $ident = str_replace( '+', 'A', $ident );
        $ident = str_replace( '/', 'B', $ident );
		$stmt = $dbh->prepare("SELECT EXISTS ( SELECT * FROM `pastes` WHERE `ident` = ? )");
		$stmt->bind_param('s', $ident );
		$stmt->execute();
		$stmt->bind_result( $_exists );
		$exists = ( $_exists == 1 ? true : false );
	}
	return $ident;
}

function limit_exceeded()
{
	global $dbh;
	global $config;

	// check day limit
	return( 
		_limit_exceeded( 'DAY', $config['limit_day']) || 
		_limit_exceeded('HOUR', $config['limit_hour'] ) 
	);

}

function _limit_exceeded( $type, $limit )
{
	global $dbh;

	if( !in_array( $type, array('DAY', 'HOUR')))
		return true;

	$stmt = $dbh->prepare("SELECT COUNT(*) FROM `pastes` WHERE `ip`= ? AND TIMESTAMPDIFF( $type, NOW(), `date` ) <= 1");
	if( !$stmt )
	{
		die("Couldn't perform throttle check");
	}
	$stmt->bind_param("s", $_SERVER['REMOTE_ADDR'] );
	$stmt->execute();
	$stmt->bind_result( $count );
	$stmt->fetch();

	return( $count > $limit );
}

check_setup();

$dbh = mysqli_connect( 
	$config['mysql_host'],
	$config['mysql_user'],
	$config['mysql_pass'],
	$config['mysql_db']
);

if( !$dbh )
{
	die("Couldn't connect to database");
}

$ident = false;

do_cleanup();

if( array_key_exists( 'p', $_GET ) && ctype_alnum( $_GET['p'] ) )
{
	$ident = $_GET['p'];
}

if( $ident )
{
	show_post( $ident );
} else if( array_key_exists( 'content', $_POST ) ) {
	do_paste();
} else {
	show_form();
}


