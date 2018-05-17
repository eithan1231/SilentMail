<?php

class tests
{
	public static function renderNavPage()
	{
		$route = router::instance();
		?>
		<!DOCTYPE html>
		<html>
			<head>
				<meta charset="utf-8">
				<title>Testing route navigation</title>
			</head>
			<body>
				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'purge-mailboxes'
					]);?>">Purge Mailboxes</a><span> - Purges all mailboxes</span>
				</div>

				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'make-mail'
					]);?>">Make Mail</a><span> - Constructs an email using email_builder</span>
				</div>

				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'mail-parse'
					]);?>">Parse Mail</a><span> - Creates a mail using the class email_builder,
						then parses it using the class email
					</span>
				</div>

				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'ui'
					], "page=landing");?>">Load UI</a><span> - Calls loadUi function with
						the GET parameter 'page'
					</span>
				</div>

				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'insert-inbox-mail'
					], "user_id=". ses_user_id. "&subject=this+is+a+test&body=your+body+oh+so+sexy");?>">Insert inbox mail</a><span> - Inserts
						a mail item to a selecte users inbox. Call this with the GET parameters 'user_id', 'subject', 'body'
					</span>
				</div>

				<div style="margin-bottom: 20px;">
					<a href="<?= $route->getRoutePath('test', [
						'action' => 'generate-insert-bulk'
					]);?>">Generate and insert bulk</a><span> - Inserts
						Generates accounts, and inserts bulk inboxs, outboxes, vmails. Can
						be useful for 'iffy queries' (queries you don't know if they'll
						impact performance of large databases).
					</span>
				</div>
			</body>
		</html>
		<?php
	}

	public static function runTest($route, $p)
	{
		switch($p['action']) {
			case "compute-password": {
				// WAS JUST TESTING SOMETHING!
				//
				$salt = [0, 0, 0, 0];
				$salt_count = count($salt);

				while(true) {
					for($i = $salt_count - 1; $i >= 0; $i--) {
						if($salt[$i] > 51) {
							continue;
						}
						else if($salt[$i] < 51) {
							$salt[$i]++;
							break;
						}
					}

					if($i === 0 && $salt[0] === 51) {
						break;
					}
				}
				break;
			}

			case "purge-mailboxes": {
				set_time_limit(500);

				sql::query("TRUNCATE TABLE `inbox`");
				sql::query("TRUNCATE TABLE `inbox_keywords`");
				sql::query("TRUNCATE TABLE `outbox`");
				sql::query("TRUNCATE TABLE `outbox_keywords`");
				sql::query("TRUNCATE TABLE `vinbox`");
				sql::query("TRUNCATE TABLE `vinbox_keywords`");

				$directories = scandir(config['mailboxDir']);
				foreach($directories as &$directory) {
					if(
						$directory == 'structure' ||
						$directory == '.' ||
						strpos($directory, '..') !== false
					) {
						continue;
					}

					unlink(config['mailboxDir'] . $directory);

					echo "Unlinking: ". htmlentities($directory) ."<br>\n<br>\n";
				}


				break;
			}

			case "make-mail": {
				$email = new email_builder(
					"James Bond",
					"jamesbond@test.com",
					"Subject#fdg",
					hash('adler32', ses_username) .'-'. hash('md5', time . uniqueToken . ses_username)
				);

				$email->addHeader('TO', 'recipient@email.com');
				$email->addRecipient('recipient2@email.com');

				$email->addBody("testing body", 'text/plain');
				$email->addBody("<div>testing body</div>", 'text/html');
				$email->addAttachment("Hello", "txt", "text/plain", "This is a sample text document");

				var_dump($email->constructMail());

				echo "\n=============================\n\n";
				var_dump($email);


				break;
			}

			case "mail-parse": {
				$email = new email_builder(
					"James Bond",
					"jamesbond@test.com",
					"Subject#fdg",
					hash('adler32', ses_username) .'-'. hash('md5', time . uniqueToken . ses_username)
				);

				$email->addHeader('TO', 'recipient@email.com');
				$email->addRecipient('recipient2@email.com');

				$email->addBody("testing body", 'text/plain');
				$email->addBody("<div>testing body</div>", 'text/html');
				$email->addAttachment("Hello", "txt", "text/plain", "This is a sample text document");

				$parsed_email = new email($email->constructMail());

				var_dump($parsed_email);

				break;
			}

			case "ui": {
				if(!isset($_GET['page'])) {
					die('unknown page');
				}

				loadUi($_GET['page']);

				break;
			}

			case "insert-inbox-mail": {

				if(
					!isset($_GET['user_id']) ||
					!isset($_GET['subject']) ||
					!isset($_GET['body'])
				) {
					die('user_id, subject, or body GET paremter is not set.');
				}

				$user = user::getUserInformation($_GET['user_id']);
				if(!$user['success']) {
					die("Unable to get user.");
				}

				$email = new email_builder(
					"(". config['projectName'] .") Test Account",
					misc::constructAddress("__r.e.s.e.r.v.e.d"),
					$_GET['subject']
				);

				$email->addRecipient(misc::constructAddress($user['data']['username']));
				$email->addBody($_GET['body'], 'text/plain');

				$success = mailbox::insertInbox(
					misc::constructAddress("__r.e.s.e.r.v.e.d"),
					[misc::constructAddress($user['data']['username'])],
					$email->constructMail()
				);

				die($success['success'] ? 'success' : $success['data']['message']);

				break;
			}

			case "generate-insert-bulk": {
				header("Content-type: text/plain");
				if(!SKIP_REGISTRATION_SECURITY_CHECKS) {
					die("SKIP_REGISTRATION_SECURITY_CHECKS is false, needs to be true.");
				}

				set_time_limit(0);
				for($i = 0; $i < 1000; $i++) {
					$username = cryptography::randomString(24);
					$username_address = misc::constructAddress($username);

					$registration = credentials::register($username, "randomshit", "", "", "", "test", "account");
					if($registration['success']) {
						$user = user::getUserInformation($registration['data']['user_id']);

						// inbox
						for($i = 0; $i < 32; $i++) {
							$email = new email_builder(
								"(". config['projectName'] .") Test Account",
								misc::constructAddress("__r.e.s.e.r.v.e.d"),
								cryptography::randomString(mt_rand(10, 32), false, true)
							);

							$email->addRecipient(misc::constructAddress($username));
							$email->addBody(cryptography::randomString(mt_rand(512, 1024), false, true), 'text/plain');

							mailbox::insertInbox(
								misc::constructAddress("__r.e.s.e.r.v.e.d"),
								[misc::constructAddress($user['data']['username'])],
								$email->constructMail()
							);

							echo "Insert for {$username}\n";
						}

						// outbox
						for($i = 0; $i < 32; $i++) {
							break;
							$email = new email_builder(
								"(". config['projectName'] .") Test Account",
								misc::constructAddress("__r.e.s.e.r.v.e.d"),
								$_GET['subject']
							);

							$email->addRecipient(misc::constructAddress($user['data']['username']));
							$email->addBody($_GET['body'], 'text/plain');

							$success = mailbox::sendMail(
								misc::constructAddress("__r.e.s.e.r.v.e.d"),
								[misc::constructAddress($user['data']['username'])],
								$email->constructMail()
							);
						}

						// vbox
						for($i = 0; $i < 5; $i++) {
							break;

							// vmail
							for($i = 0; $i < 10; $i++) {

							}
						}
					}
					else {
						echo "Unable to register account, {$username}, why? {$registration['data']['message']}\n";
					}
				}

				break;
			}

			default: {
				die("Unknown action");
				break;
			}
		}
	}
}
