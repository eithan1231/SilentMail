<?php

$router = router::instance();
$user_id = user::getUserId(PROFILE_PAGE_USERNAME);
if($user_id === false) {
  // Username not found
  $router->redirectRoute('landing');
}

// Checking that we can view user page
if(!preferences::getPreference('allow_profile_page', $user_id)) {
  // User doesnt want his profile page publicly viewable
  $router->redirectRoute('landing');
}

// Getting user inforamtion
$user_information = user::getUserInformation($user_id);
if(!$user_information['success']) {
  $router->redirectRoute('landing');
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <?php if (!preferences::getPreference('hide_full_name', $user_id)): ?>
      <title><?= misc::buildTitle('Profile of '. clean_name($user_information['data']['name_full'])) ?></title>
    <?php else: ?>
      <title><?= misc::buildTitle('Profile of '. $user_information['data']['username']) ?></title>
    <?php endif; ?>
    <link rel="shortcut icon" href="<?= assetloader::getAssetPath($router, "favicon", "ico"); ?>" />

		<!-- Styles -->
		<link href="//fonts.googleapis.com/css?family=Roboto:400" rel="stylesheet">
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "main", "css"); ?>"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "profile_page", "css"); ?>"></link>

		<!-- Scripts -->
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "context-menu", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "templates", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "library", "js"); ?>"></script>

		<!-- JS Snippet -->
		<?php javascript::getJsSnippet(); ?>

		<!-- Javascript entry point -->
		<script>
		window.onload = function() {
			ContextMenu.initialize();
			Library.initialize();
		};
		</script>
  </head>
  <body>
    <div class="pp-header">
      <a class="pp-item" href="<?= router::instance()->getRoutePath('landing') ?>">Home</a>
    </div>

    <div class="pp-profile">
      <div class="pp-profile-image-container noselect">
        <img class="pp-profile-image" src="<?= assetloader::getAssetPath($router, "unknown-profile-picture", "png"); ?>" alt="Image unable to load" width="200px" />
      </div>
      <?php if (preferences::getPreference('hide_full_name', $user_id)): ?>
        <div class="pp-profile-about-container">
          <div class="pp-profile-name">
            <a class="pp-profile-name-main-text clean-a black-a" href="mailto:<?= esc(misc::constructAddress($user_information['data']['username'])) ?>">
              <?= esc(misc::constructAddress($user_information['data']['username'])) ?>
            </a>
          </div>
        </div>

      <?php else: ?>
        <div class="pp-profile-about-container">
          <div class="pp-profile-name">
            <a class="pp-profile-name-main-text clean-a black-a" href="mailto:<?= esc(misc::constructAddress($user_information['data']['username'])) ?>">
              <?= esc(clean_name($user_information['data']['name_full'])) ?>
            </a>
          </div>
          <div class="pp-profile-name">
            <span class="pp-profile-name-sub-text"><?= esc(misc::constructAddress($user_information['data']['username'])) ?></span>
          </div>

          <div class="pp-about">

          </div>
        </div>
      <?php endif; ?>


    </div>
  </body>
</html>
