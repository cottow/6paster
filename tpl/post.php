<?php
if (isset($_GET['smart']) && substr($content, 0, 5) == '<?php')
{
	header('Content-type: text/html');
	echo '<!DOCTYPE html>
<html>
	<head>
		<title>Paste: PHP sourcecode</title>
	</head>
	<body>
		<main>';
	highlight_string($content);
	echo '</main>
	</body>
</html>';
}
else
{
	echo $content;
}
