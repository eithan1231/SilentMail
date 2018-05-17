<?php

$router = router::Instance();

$categories_count = blog::getCategoryCount();
$categories = blog::getCategories(0);

if($categories_count === 0) {
	$router->redirectRoute('landing');
}

?><!DOCTYPE html>
<html>
	<head>
		<?php if ($categories_count == 1): ?>
			<title><?= misc::buildTitle($categories[0]['name'] ." Blog"); ?></title>
		<?php else: ?>
			<title><?= misc::buildTitle("Blog"); ?></title>
		<?php endif; ?>

		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "blog.landing", "css"); ?>"></link>
		<link rel="stylesheet" href="<?= assetloader::getAssetPath($router, "main", "css"); ?>"></link>
		<!-- <script src="<?= assetloader::getAssetPath($router, "templates", "js"); ?>"></script> -->

	</head>
	<body>
		<div id="category-container">
			<?php if ($categories_count >= 1): ?>
				<?php foreach ($categories as &$value): ?>
					<?php
					$category_threads = blog::getBlogThreads(0, 5, $value['id'], true);
					?>
					<div class="category">
						<div class="header-container">
							<h1 class="header">
								<a href="<?= blog::getCategoryRoute($value['id']) ?>" class="clean-a black-a">
									<?= esc($value['name']); ?>
								</a>
							</h1>
						</div><!-- header-container -->
						<?php foreach ($category_threads as &$thread): ?>
							<div class="thread">
								<div class="top-container">
									<h3 class="header">
										<a class="clean-a black-a" href="<?= blog::getThreadRoute($value['id'], $thread['id']); ?>">
											<?= esc($thread['title']); ?>
										</a>
									</h3>
									<div class="misc-container">
										<span class="misc">
											By <span style="color:<?= esc($thread['creator_color']); ?>;"><?= esc(str_smallify(clean_name($thread['creator_full_name']), 32)); ?></span>, At <?= time::formatFromPresent($thread['date']); ?>
										</span>
									</div>
								</div>
								<div class="body">
									<?= esc(str_smallify($thread['body'], 512)); ?>

									<div style="margin-top: 10px;">
										<a href="<?= blog::getThreadRoute($value['id'], $thread['id']); ?>" class="clean-a">Read More</a>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div><!-- category -->
				<?php endforeach; ?>
			<?php else: ?>

			<?php endif; ?>
		</div>

	</body>
</html>
