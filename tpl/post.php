<?php
if (substr($content, 0, 5) == '<?php')
{
	header('Content-type: text/html');
	echo '<html><body>';
	highlight_string($content);
	echo '</body></html>';
}
else
{
	echo $content;
}
