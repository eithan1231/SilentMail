<?php
$router = router::instance();

$security_question = security_questions::getPair(ses_user_id);
if($security_question === false) {
	die("No security questions linked with account.");
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= misc::buildTitle("Security Check"); ?></title>
		<link rel="shortcut icon" href="<?= assetloader::getAssetPath($router, "favicon", "ico"); ?>" />

		<?php javascript::getJsSnippet(); ?>

		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "main", "css"); ?>"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "auth", "css"); ?>"></link>
		</script>

	</head>
	<body>
		<div id="auth-screen">
			<div id="template-container">

				<!-- I was loading this over templates, but was too annoying, so here i am. -->


				<form method="POST" action="<?= htmlentities(post::getPostRoute('security-check')); ?>" id="template-security-check">
					<h1 class="noselect">Security Check</h1>

					<?php if(strlen($security_question['question']) > 0): ?>
						<input type="hidden" name="id" value="<?= htmlentities($security_question['id']); ?>"></input>

						<div class="input-div">
							<div class="span-container">
								<span class="span input-span"><?= htmlentities($security_question['question']); ?></span>
							</div>

							<input name="answer" class="clean-textbox" type="text" placeholder="Answer" autofocus required></input>
						</div>

						<?php if(strlen($security_question['hint']) > 0): ?>
							<div class="input-div">
								<div class="span-container">
									<span class="span input-span">Hint</span>
								</div>

								<span><i><?= htmlentities($security_question['hint']); ?></i></span>
							</div>

						<?php endif; ?>

						<?php if(isset($_GET['s'])): ?>
							<div id="misc-container" class="noselect">
								<div class="sub-container">
									<span id="status">
										<?= htmlentities($_GET['s']); ?>
									</span>
								</div>
							</div>
						<?php endif; ?>

						<button class="button">Verify</button>

					<?php else: ?>
						<div class="input-div">
							<span>Your security question's question is invalid. So therefore you're unable to access account.</span>
						</div>
					<?php endif; ?>
				</form>
			</div>
		</div>
	</body>
</html>
