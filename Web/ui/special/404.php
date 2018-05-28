<!DOCTYPE html>
<html>
	<head>
		<title>Requested page not found</title>
	</head>

	<body>
		<h1>
			Page not found!
		</h1>
		<p1>
			The requested page <code><?= esc($_SERVER['REQUEST_URI']); ?></code> could not be found. <a href="<?= router::instance()->getRoutePath('landing'); ?>">Click here</a> to go home.
		</p1>
	</body>
</html>
