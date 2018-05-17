<?php
$router = router::instance();

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= misc::buildTitle("Authentication"); ?></title>
		<link rel="shortcut icon" href="<?= assetloader::getAssetPath($router, "favicon", "ico"); ?>" />
		<meta name="robots" content="noindex, nofollow">

		<?php javascript::getJsSnippet(); ?>

		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "auth", "css"); ?>"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "main", "css"); ?>"></link>
		<script src="<?= assetloader::getAssetPath($router, "templates", "js"); ?>"></script>
		<script src="<?= assetloader::getAssetPath($router, "library", "js"); ?>"></script>
		<script src="<?= assetloader::getAssetPath($router, "auth", "js"); ?>"></script>

		<script>
		window.onload = function() {
			Library.initialize();
			let templateString = "template-login";
			if(window.location.hash === '#register') {
				templateString = "template-register";
			}
			TemplateEngine.getAndSetTemplate(templateString, 'template-container');
		};
		</script>
	</head>
	<body>
		<div id="auth-screen">
			<div id="template-container">
				<noscript>
					<h1 class="noselect">JavaScript is required, get a browser that supports JavaScript.</h1>
				</noscript>
			</div>
		</div>
	</body>
</html>
