<?php
$router = router::Instance();
$notifications = notifications::getUnread();
$notifications_count = ($notifications === false ? 0 : count($notifications));
?>
<!DOCTYPE html>
<html>
	<head>
		<!-- Misc -->
		<title><?= misc::buildTitle("Mail Home"); ?></title>
		<link rel="shortcut icon" href="<?= assetloader::getAssetPath($router, "favicon", "ico"); ?>" />

		<!-- Styles -->
		<link href="//fonts.googleapis.com/css?family=Roboto:400" rel="stylesheet">
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "main", "css"); ?>"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "mail", "css"); ?>"></link>

		<!-- Scripts -->
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "mail", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "context-menu", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "search", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "templates", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "library", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "vmail", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "vbox", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "newmail", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath($router, "auth", "js"); ?>"></script>

		<!-- JS Snippet -->
		<?php javascript::getJsSnippet(); ?>

		<!-- Javascript entry point -->
		<script>
		window.onload = function() {
			ContextMenu.initialize();
			Library.initialize();
			Tab.changeTab('template-inbox', 'tab-inbox', 'tab-body');
		};
		</script>
	</head>
	<body>
		<div id="header">
			<div id="user-container">
				<!--  -->
				<span class="noselect" title="<?= esc(misc::constructAddress(ses_username)); ?>">
					Welcome back, <a class="color-inherit" href="mailto:<?= esc(misc::constructAddress(ses_username)) ?>"><?= esc(str_smallify(clean_name(ses_username), 30)); ?></a>.
				</span>
			</div>

			<div id="search-container">
				<input id="search-query" type="text" placeholder="Search" onkeyup="Search.onKeyUpInputHandler(event);"></input>
				<button id="submit-button" class="noselect" onclick="Search.autoSearch();">Search</button>
			</div>
		</div>

		<div id="body">
			<div class="noselect" id="sidebar-settings">

				<div class="sidebar-item">
					<div id="notification-container">
						<a href="javascript://" class="text" onclick="ContextMenu.open('notification-dropdown'); return false; ">
							Notifications
							<?php if ($notifications_count > 0): ?>
								<span class="count"><?= $notifications_count ?></span>
							<?php endif; ?>
						</a>
						<div id="notification-dropdown" class="dropdown" hidden>
							<div class="item">
								<a href="#" onclick="Tab.changeTab('template-notifications', false, 'tab-body'); return false;">
									<span class="item-text">
										All Notifications
									</span>
								</a>
							</div>

							<?php if($notifications !== false): ?>
								<div style="margin-top: 10px;"></div>
								<?php foreach ($notifications as &$notification): ?>
									<div class="item" title="<?= htmlentities($notification['text']); ?>">
										<a href="<?= htmlentities(notifications::getRedirectRoute($notification['id'])); ?>" target="_blank">
											<span class="item-text">
												<?= htmlentities(str_smallify($notification['text'], 27)); ?>
											</span>
										</a>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="sidebar-item">
					<a href="javascript://" class="text" onclick="Tab.changeTab('template-general-settings', false, 'tab-body'); return false;">
						General Settings
					</a>
				</div>

				<div class="sidebar-item">
					<a href="javascript://" class="text" title="Virtual mail." onclick="Tab.changeTab('template-virtual-emails', false, 'tab-body');  return false;">
						vMail
					</a>
				</div>

				<div class="sidebar-item">
					<a href="javascript://"  class="text" onclick="Tab.changeTab('template-login-logs', false, 'tab-body'); return false;">
						Access Logs
					</a>
				</div>

				<?php if (preferences::getPreference('allow_profile_page')): ?>
					<div class="sidebar-item">
						<a class="text" href="<?= router::instance()->getRoutePath('profile_page', [
							'username' => ses_username
						]) ?>" target="_blank">
							Profile Page
						</a>
					</div>
				<?php endif; ?>

				<div class="sidebar-item">
					<a class="text" href="<?= router::instance()->getRoutePath('logout', ['security_token' => security_token]); ?>">
						Logout
					</a>
				</div>

				<?php if (preferences::getPreference('technical_mode')): ?>
					<div style="margin-top: 30px;">
						<span style="margin-left: 20px; font-size: 13px;">-- Developers --</span>
					</div>

					<div class="sidebar-item">
						<a href="javascript://" class="text" onclick="Tab.changeTab('template-api', false, 'tab-body'); return false;">
							API
						</a>
					</div>

					<div class="sidebar-item">
						<a href="javascript://" class="text" onclick="Tab.changeTab('template-web-hooks', false, 'tab-body'); return false;">
							Web Hooks
						</a>
					</div>
				<?php endif; ?>
			</div><!-- sidebar-settings -->

			<div id="tab-container">

				<div id="tab-heading" class="noselect">
					<div id="tab-inbox" class="tab-control normal" onclick="Tab.changeTab('template-inbox', 'tab-inbox', 'tab-body'); return false;">
						<div class="tab-control-inner-div">
							Inbox
						</div>
					</div>

					<div id="tab-sent" class="tab-control normal" onclick="Tab.changeTab('template-sent', 'tab-sent', 'tab-body'); return false;">
						<div class="tab-control-inner-div">
							Outbox
						</div>
					</div>

					<div id="tab-new" class="tab-control normal" onclick="Tab.changeTab('template-new', 'tab-new', 'tab-body'); return false;">
						<div class="tab-control-inner-div">
							Compose
						</div>
					</div>

				</div>
				<div id="tab-body">
					<noscript>
						<!-- No Javascript... -->
						<h1>Javascript not found!</h1>
						<p2>Please enable JavaScript or get a browser that supports it.</p2>
					</noscript>
				</div>
			</div><!-- tab-container -->
		</div><!-- body -->
	</body>
</html>
