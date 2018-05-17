<?php
$notifications = notifications::getUnread();
$notifications_count = is_array($notifications) ? count($notifications) : 0;
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= misc::buildTitle("Welcome"); ?></title>
		<link rel="shortcut icon" href="<?= assetloader::getAssetPath(router::instance(), "favicon", "ico"); ?>" />

		<?php javascript::getJsSnippet(); ?>

		<style>
		#lp-top {
			background-image: url(<?= assetloader::getAssetPath(router::instance(), 'landing-top-background', 'jpg'); ?>);
		}
		</style>

		<link href="//fonts.googleapis.com/css?family=Roboto:400" rel="stylesheet">
		<link rel="stylesheet" href="<?= assetloader::getAssetPath(router::instance(), "landing", "css"); ?>" />
		<link rel="stylesheet" href="<?= assetloader::getAssetPath(router::instance(), "main", "css"); ?>" />

		<script type="text/javascript" src="<?= assetloader::getAssetPath(router::instance(), "library", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath(router::instance(), "context-menu", "js"); ?>"></script>
		<script type="text/javascript" src="<?= assetloader::getAssetPath(router::instance(), "auth", "js"); ?>"></script>

		<script>
		<!--
		window.onload = function() { ContextMenu.initialize(); };
		// -->
		</script>
	</head>

	<body class="noselect">
		<div id="lp-top">
			<div id="lp-top-nav">
				<div style="margin-left:16%;float:left;height: 5px;"><!-- padding --></div>

				<?php if (ses_logged_in): ?>
					<div class="lp-top-nav-button normal">
						<a href="<?= router::instance()->getRoutePath("mail"); ?>" class="text">Mailbox</a>
					</div>

					<?php if ($notifications !== false): ?>
						<div class="lp-top-nav-button normal">
							<a href="#" onclick="ContextMenu.open('notification-dropdown')" class="text">Notifications</a>

							<div id="notification-dropdown" class="dropdown" hidden>
								<?php foreach ($notifications as &$value): ?>
									<div class="item<?= (($value['__index__'] === 0) ? ' item-top' : false) ?><?= (($value['__index__'] === $notifications_count - 1) ? ' item-bottom' : false) ?><?= (($value['__index__'] !== $notifications_count - 1 && ($value['__index__'] !== 0)) ? ' item-middle' : false) ?>">
										<a href="<?= notifications::getRedirectRoute($value['id']); ?>" class="text" target="_blank">
											<?= htmlentities(str_smallify($value['text'], 24)); ?>
										</a>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

				<?php else: ?>
					<div class="lp-top-nav-button normal">
						<a href="<?= router::instance()->getRoutePath("authenticate"); ?>" class="text">Login</a>
					</div>

					<div class="lp-top-nav-button other">
						<a href="<?= router::instance()->getRoutePath("authenticate", false, "#register"); ?>" class="text">Register</a>
					</div>

				<?php endif; ?>

				<?php if (config['blogEnabled']): ?>
					<div class="lp-top-nav-button other">
						<a href="<?= router::instance()->getRoutePath("blogLanding"); ?>" class="text">Blog</a>
					</div>

				<?php endif; ?>

			</div><!-- lp-top-nav -->

			<div id="lp-top-description">
				<h1 id="lp-top-description-title"><?= config['projectName']; ?> Mail</h1>

				<p id="lp-top-description-paragraph">
					<?= config['projectName']; ?> is a basic email service made by a single
					programmer, for the world to enjoy. We promise Anonymity, Security,
					all with little to no limits!
				</p>
			</div><!-- lp-top-description -->

			<?php if(ses_logged_in): ?>
				<div id="lp-logged-in-misc">
					<span style="color: #f3f3f3;">
						Welcome back, <?= htmlentities(ses_username); ?>.
					</span>
				</div><!-- lp-logged-in-misc -->
			<?php else: ?>
				<div id="lp-register">
					<h2 class="txt-white">Registration</h2>

					<div class="input-div">
						<!-- Username -->
						<div class="span-container">
							<span class="txt-white input-span">Username</span>
						</div>

						<input id="register-username" class="input-textbox" type="text" placeholder="Username" autofocus></input>
					</div>


					<div class="input-div">
						<!-- Password -->
						<div class="span-container">
							<span class="txt-white input-span">Password</span>
						</div>

						<input id="register-password" class="input-textbox" type="password" placeholder="Password"></input>
					</div>

					<div class="input-div">
						<!-- Password -->
						<div class="span-container">
							<span class="txt-white input-span">Full Name</span>
						</div>

						<input id="register-first-name" class="input-textbox" type="text" placeholder="First name" required></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-last-name" class="input-textbox" type="text" placeholder="Last name" required></input>
					</div>

					<div class="input-div">
						<!-- Security Questions -->
						<div class="span-container">
							<span class="txt-white input-span">Security Question</span>
						</div>

						<input id="register-question" class="input-textbox" type="text" placeholder="Question" title="Example: What's your mothers name?"></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-answer" class="input-textbox" type="text" placeholder="Answer" title="Example: Katrina"></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-hint" class="input-textbox" type="text" placeholder="Hint" title="Example: Name starting with a K"></input>
					</div>

					<div id="misc-container">
						<div class="sub-container">
							<code>
								<span id="auth-status" hidden></span>
							</code>
						</div>

						<!--<div class="sub-container">
							<span style="font-size: 13px;">By clicking submit you agree to our <a href="#">Terms of Use</a></span>
						</div>-->
					</div>

					<button onclick="Auth.autoRegister();" class="button">Submit</button>

				</div><!-- lp-register -->
			<?php endif; ?>
		</div>

		<div class="lp-section" align="center">
			<div class="lp-section-3div">
				<div class="image">

				</div>

				<h2>Anonymity</h2>
				<p>
					At <?= config['projectName']; ?>, we take steps to make sure your
					anonymity stays anonymous. Your anonymity, is our anonymity.
				</p>
			</div>

			<div class="lp-section-3div">
				<div class="image">

				</div>

				<h2>Security</h2>
				<p>
					We beleive everyone has the right to be secure when browsing the internet.
					So here at <?= config['projectName']; ?>, we are doing everthing
					possible to make your account secure.
				</p>
			</div>

			<div class="lp-section-3div">
				<div class="image">

				</div>

				<h2>Limits</h2>
				<p>
					<?= config['projectName']; ?> does not have may limits. The only limits
					we have are theoretical ones.
				</p>
			</div>
		</div>

		<div class="lp-section invert">
			<h1 id="vmail-header">vMail</h1>

			<h2 class="vmail-sub-header">What is vMail?</h2>
			<p class="vmail-paragraph">
				vMail, sometimes refereed to as a virtual mailbox, is a mailbox
				seperated from that main mailbox. This way you can receive mail to
				a virtual address rather then your main. You can also disable, and
				reenable virtual mailboxes at anytime.
			</p>

			<h2 class="vmail-sub-header">Why use vMail?</h2>
			<p class="vmail-paragraph">
				If you signup to a lot of new websites, you often require email
				verification. Rather then exposing your main email address, you can
				put a virtual email address. This way you will not get irritating updates
				in your main mailbox, or any other types unneeded mail.
			</p>
		</div>

		<div id="footer">

		</div>
	</body>
</html>
