<?php

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex,nofollow">
		<title>Account Disabled</title>
	</head>
	<body>
		<h1>Your account has been disabled</h1>
		<p>
			Your account, <span style="color: <?= esc(ses_group_color) ?>;"><?= esc(ses_username); ?></span>, has been disabled.
		</p>
	</body>
</html>
