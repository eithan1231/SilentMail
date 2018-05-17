<?php
ob_start(function($buffer) {
	// Poor mans minifier
	return str_replace(["\r", "\n", "\t"], '', $buffer);
}, 1024);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Internal Error (500)</title>
		<meta name="robots" content="noindex,nofollow">
	</head>
	<body align="center">
		<div style="display:inline-table;width:400px">
			<h1>Uh oh!</h1>
			<p>
				An internal error has occurred. A team of highly underpaid developers are
				working to fix this error, so bare with us.
			</p>
		</div>
	</body>
</html>
