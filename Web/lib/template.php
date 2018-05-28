<?php

class template
{
	public static function outputTemplate($template_name, $token)
	{
		global $cache;
		if(templateToken !== $token) {
			return false;
		}

		header("Content-type: text/plain");

		if(!isset($_SERVER['HTTP_REFERER'])) {
			// Stop people accessing the templates directly.
			return false;
		}

		switch($template_name) {

			// =======================================================================
			// Mail page.
			// =======================================================================

			case "template-inbox": {
				// =====================================================================
				// Users inbox
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				// Getting user input (used for pages.)
				$index = 0;
				$per_page = 32;
				$is_vbox = false;
				$vbox_id = 0;
				if(isset($_GET['pp']) && isset($_GET['i'])) {
					$index = intval($_GET['i']);
					$per_page = intval($_GET['pp']);
				}
				if(isset($_GET['vbox_id'])) {
					$is_vbox = true;
					$vbox_id = intval($_GET['vbox_id']);
				}

				// Getting inbox
				$inbox = ($is_vbox
					? vmailbox::getVBoxInbox($vbox_id, $index, $per_page)
					: mailbox::getInbox(ses_user_id, $index, $per_page)
				);

				// Getting the total size of mailbox.
				$inbox_size = 0;
				if($inbox['success']) {
					$inbox_size = ($is_vbox
						? vmailbox::getVBoxInboxCount($vbox_id)
						: mailbox::getInboxCount(ses_user_id)
					);
				}

				// Storing variables for getting page buttons
				$page_count = round($inbox_size / $per_page);
				$current_page = round($index / $per_page) + 1;

				// buttons that have been rendered... to save a pain in the ass
				$rendered_buttons = [];

				// function for rendering a page button.
				$render_page_button = function($offset) use($page_count, $per_page, $index, &$rendered_buttons) {
					// Calculating the page index
					$page_index = round($offset * $per_page);

					// Making sure this button hasn't been rendered
					if(in_array($page_index, $rendered_buttons)) {
						return;
					}
					$rendered_buttons[] = $page_index;

					// Rendering
					$page_query = "i={$page_index}&pp={$per_page}";
					?>
					<a href="javascript://" class="<?= $page_index == $index ? 'current-page-button' : 'page-button' ?> noselect"
						onclick="Tab.changeTab('template-inbox', 'tab-inbox', 'tab-body', '<?= esc($page_query); ?>'); return false;">
						<?= ($offset + 1) ?>
					</a>
					<?php
				};

				?>

				<?php if($inbox['success']): ?>
					<?php foreach($inbox['data']['mail'] as $mail): ?>
						<div id="mail-in-<?= $mail['id']; ?>"
							onclick="Tab.changeTab('template-view-in-email', false, 'tab-body', 'id=<?= intval($mail['id']) . ($is_vbox ? "&vbox_id={$mail['receiver']}" : ''); ?>')"
							class="mailitem <?= ($mail['has_seen'] ? 'seen' : 'normal'); ?> noselect">
							<!--<div class="checkbox-container">
								<input type="checkbox" class="checkbox" onclick=""></input>
							</div>-->
							<?php if(!$mail['is_sender_verified']): ?>
								<!--<div style="background-color:red;margin-right:3px;" class="circle" title="Unverified sender"></div>-->
							<?php endif; ?>

							<div class="user-container" title="Sender: <?= htmlentities($mail['sender_address']); ?>">
								<span class="user-span">
									<?= htmlentities(str_smallify($mail['sender_name'], /*18*/33)); ?>
								</span>
							</div>

							<div class="subject-container" title="Subject: <?= htmlentities($mail['subject']); ?>">
								<span class="subject-span">
									<?= htmlentities(str_smallify($mail['subject'], 70)); ?>
								</span>
							</div>

							<div class="date-container">
								<span class="date-span">
									<?= time::formatFromPresent($mail['time']); ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>

					<?php if($page_count >= 2): ?>
						<?php
						// -----------------------------------------------------------------
						// Rending page buttons
						// -----------------------------------------------------------------
						?>
						<div class="page-button-container">
							<?php
							if($page_count <= 6) {
								for($i = 0; $i < $page_count; $i++) {
									$render_page_button($i);
								}
							}
							else {
								if(
									$current_page < 3 ||
									$current_page > $page_count - 3
								) {
									$render_page_button(0);
									$render_page_button(1);
									$render_page_button(2);
									if($current_page == 2) {
										$render_page_button(3);
									}

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									if($current_page == $page_count - 2) {
										$render_page_button($page_count - 4);
									}
									$render_page_button($page_count - 3);
									$render_page_button($page_count - 2);
									$render_page_button($page_count - 1);
								}
								else {
									$render_page_button(0);
									$render_page_button(1);
									$render_page_button(2);

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									$render_page_index = $current_page - 1;
									for($i = 0; $i < 3; $i++) {
										$render_page_button($render_page_index + $i);
									}

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									$render_page_button($page_count - 3);
									$render_page_button($page_count - 2);
									$render_page_button($page_count - 1);
								}
							}

							?>
						</div>
						<?php
						// -----------------------------------------------------------------
						// End of page button rendering
						// -----------------------------------------------------------------
						?>
					<?php endif; ?>
				<?php else: ?>
					<h2 class="noselect"><?= htmlentities($inbox['data']['message']); ?></h2>
				<?php endif; ?>
				<?php
				break;
			}

			case "template-sent": {
				// =====================================================================
				// Users sent mailbox
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				// Getting user input (used for pages.)
				$index = 0;
				$per_page = 32;
				$outbox_size = 0;
				if(isset($_GET['pp']) && isset($_GET['i'])) {
					$index = intval($_GET['i']);
					$per_page = intval($_GET['pp']);
				}

				// Getting current mailbox size
				if($outbox = mailbox::getOutbox(ses_user_id, $index, $per_page)) {
					$outbox_size = mailbox::getOutboxCount(ses_user_id);
					$page_count = round($outbox_size / $per_page);
					$current_page = round($index / $per_page) + 1;
				}

				// buttons that have been rendered... to save a pain in the ass
				$rendered_buttons = [];

				// function for rendering a page button.
				$render_page_button = function($offset) use($page_count, $per_page, $index, &$rendered_buttons) {
					// Calculating the page index
					$page_index = round($offset * $per_page);

					// Making sure this button hasn't been rendered
					if(in_array($page_index, $rendered_buttons)) {
						return;
					}
					$rendered_buttons[] = $page_index;

					// Rendering
					$page_query = "i={$page_index}&pp={$per_page}";
					?>
					<a href="javascript://" class="<?= $page_index == $index ? 'current-page-button' : 'page-button' ?> noselect"
						onclick="Tab.changeTab('template-sent', 'tab-sent', 'tab-body', '<?= esc($page_query); ?>'); return false;">
						<?= ($offset + 1) ?>
					</a>
					<?php
				};

				?>
				<?php if($outbox['success']): ?>
					<!-- State Success - output emails -->

					<?php foreach ($outbox['data']['mail'] as $key => $value): ?>
						<div id="mail-out-<?= esc($key); ?>" class="mailitem normal noselect">
							<div class="user-container">

								<?php if($value['recipients_count'] > 1): ?>
									<span class="user-span" title="<?= htmlentities($value['recipients'][0]); ?>">
										&lt;<?= htmlspecialchars(str_smallify($value['recipients'][0], 13)) ?>&gt; and <?= ($value['recipients_count'] - 1); ?> other<?= ($value['recipients_count'] > 2 ? 's' : ''); ?>.
									</span>

								<?php else: ?>
									<span class="user-span" title="<?= htmlentities($value['recipients'][0]); ?>">
										<?= htmlspecialchars(str_smallify($value['recipients'][0], 28)); ?>
									</span>

								<?php endif; ?>

							</div>
							<div class="subject-container">
								<span class="subject-span"><?=  htmlspecialchars(str_smallify($value['subject'], 71)); ?></span>
							</div>
							<div class="date-container">
								<span class="date-span"><?= time::formatFromPresent($value['time']); ?></span>
							</div>
						</div>

					<?php endforeach; ?>

					<?php if($page_count >= 2): ?>
						<?php
						// -----------------------------------------------------------------
						// Rending page buttons
						// -----------------------------------------------------------------
						?>
						<div class="page-button-container">
							<?php
							if($page_count <= 6) {
								for($i = 0; $i < $page_count; $i++) {
									$render_page_button($i);
								}
							}
							else {
								if(
									$current_page < 3 ||
									$current_page > $page_count - 3
								) {
									$render_page_button(0);
									$render_page_button(1);
									$render_page_button(2);
									if($current_page == 2) {
										$render_page_button(3);
									}

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									if($current_page == $page_count - 2) {
										$render_page_button($page_count - 4);
									}
									$render_page_button($page_count - 3);
									$render_page_button($page_count - 2);
									$render_page_button($page_count - 1);
								}
								else {
									$render_page_button(0);
									$render_page_button(1);
									$render_page_button(2);

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									$render_page_index = $current_page - 1;
									for($i = 0; $i < 3; $i++) {
										$render_page_button($render_page_index + $i);
									}

									echo "<span class=\"dot-dot-dot noselect\">...</span>";

									$render_page_button($page_count - 3);
									$render_page_button($page_count - 2);
									$render_page_button($page_count - 1);
								}
							}

							?>
						</div>
						<?php
						// -----------------------------------------------------------------
						// End of page button rendering
						// -----------------------------------------------------------------
						?>
					<?php endif; ?>
				<?php else: ?>
					<h2 class="noselect" title="Detailed: <?= esc($outbox['data']['message']); ?>">You have sent no mail</h2>
				<?php endif; ?>
				<?php
				break;
			}

			case "template-new": {
				// =====================================================================
				// Template for composing a new message
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>
				<div id="componse-new">
					<div class="input-div">
						<div class="span-container">
							<span class="span input-span">Recipients</span>
						</div>

						<input id="componse-recipients" type="text" placeholder="Recipients" title="For multiple, split them with a comma" autofocus></input>
					</div>


					<div class="input-div">
						<div class="span-container">
							<span class="span input-span">Subject</span>
						</div>

						<input id="componse-subject" type="text" placeholder="Subject"></input>
					</div>

					<div class="input-div">
						<textarea id="componse-body" placeholder="Body"></textarea>
					</div>

					<button class="button" onclick="newmail.autoSubmit();">Submit</button>
				</div>
				<?php
				break;
			}

			case "template-view-in-email": {
				// =====================================================================
				// Template for viewing a received email
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				if(!isset($_GET['id'])) {
					return false;
				}

				$vbox_mode = isset($_GET['vbox_id']);
				$inbox_id = intval($_GET['id']);
				$vbox_id = -1;

				if($vbox_mode) {
					$vbox_id = intval($_GET['vbox_id']);
				}

				$cache_key = $cache->buildKey('inbox-item-body', [$vbox_mode, $inbox_id, $vbox_id]);
				$cached = $cache->exists($cache_key);

				$inboxy_item = ($vbox_mode
					? vmailbox::getVBoxInboxItem($vbox_id, $inbox_id, ses_user_id, !$cached)
					: mailbox::getInboxItem($inbox_id, ses_user_id, !$cached)
				);

				// making mail as read
				if($vbox_mode) {
					vmailbox::markVBoxItemRead($vbox_id, $inbox_id);
				}
				else {
					mailbox::markInboxItemRead($inbox_id);
				}

				$body = "<h2 style=\"color: red;\">Unable to laod body</h2>";

				if(!$cached && $inboxy_item['data']['mail']) {
					$mail = &$inboxy_item['data']['mail'];
					$mail_content_type = $mail->getContentType();

					$handleMultipart = function(&$body_parts) use(&$handleMultipart, &$setBody) {
						foreach($body_parts as &$body_part) {
							if($body_part['content-type']['type'] === 'multipart') {
								$handleMultipart($body_part['body-parts']);
								continue;
							}

							if($body_part['content-type']['type'] === 'text') {
								if($setBody($body_part['body'], $body_part['content-type'])) {
									break;
								}
							}
						}
					};

					$setBody = function($text, &$content_type) use(&$body) {
						switch($content_type['subtype']) {
							case "html": {
								$body = html_sanitize::sanitize($text);
								return true;
							}

							case "plain": {
								$body = htmlspecialchars($text);
								return true;
							}

							default: {
								return false;
							}
						}
					};

					if($mail_content_type['type'] === 'text') {
						$setBody($mail->getBody(), $mail_content_type);
					}
					else if($mail_content_type['type'] === 'multipart') {
						$body_parts = $mail->getBodyParts();
						$handleMultipart($body_parts);
					}

					// Storing in cache
					$cache->store($cache_key, $body);
				}
				else if($cached) {
					$body = $cache->get($cache_key);
				}

				?>

				<div id="view-inbox-mail">
					<div id="subject-container">
						<span id="subject"><?= htmlentities(str_smallify($inboxy_item['data']['subject'], 127)); ?></span>
					</div>
					<div id="sender-container">
						<?php if($inboxy_item['data']['sender_name'] == $inboxy_item['data']['sender_address']): ?>
							<span id="sender"><b><?= htmlentities($inboxy_item['data']['sender_address']); ?></b></span>
						<?php else: ?>
							<span id="sender">
								<span id="name"><?= htmlentities($inboxy_item['data']['sender_name']); ?></span> &lt;<?= htmlentities($inboxy_item['data']['sender_address']); ?>&gt;
							</span>
						<?php endif; ?>
					</div>
					<?php if (preferences::getPreference('technical_mode')): ?>
						<div>
							<a class="dropdown-link" href="#" onclick="ContextMenu.open('view-in-technical-dropdown'); return false;">
								Technical Menu
							</a>
							<div class="dropdown dropdown-menu" id="view-in-technical-dropdown" hidden>
								<h3 class="header">Technical</h3>

								<?php if ($vbox_mode): ?>
									<span>No vMail menu</span>
								<?php else: ?>
									<div class="item">
										<a class="text" href="<?= mailbox::getInboxMailRoute($inbox_id) ?>" target="_blank">
											Export Raw Mail
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<div id="body-container">
						<?= $body; ?>
					</div>

					<?php if($inboxy_item['data']['mail_attachments_count'] > 0): ?>
						<div class="noselect" id="attachment-container">
							<?php foreach ($inboxy_item['data']['mail_attachments'] as &$attachment): ?>
								<?php
								if(isset($attachment['inline']) && $attachment['inline']) {
									continue;
								}
								?>

								<div class="attachment" title="<?= htmlentities($attachment['name']); ?>">
									<?php if ($vbox_mode): ?>
										<a href="<?= vmailbox::getVBoxInboxAttachmentRoute($vbox_id, $inbox_id, $attachment['internal-name']); ?>" target="_blank">
									<?php else: ?>
										<a href="<?= mailbox::getInboxAttachmentRoute($inbox_id, $attachment['internal-name']); ?>" target="_blank">
									<?php endif; ?>
										<div class="icon">
											<img src="<?= assetloader::getAssetPath(false, 'attachment', 'png'); ?>"></img>
										</div>
										<span class="name" title="<?= htmlentities($attachment['name']); ?>">
											<?= htmlentities(str_smallify($attachment['name'], 9)); ?>
										</span>
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div id="reply-container">
						<input id="reply-recipient" type="hidden" value=""></input>
						<input id="reply-subject" type="hidden" value=""></input>

						<div>
							<textarea id="reply-body" placeholder="Quick Reply"></textarea>
						</div>

						<button class="button">Submit</button>
					</div>

				</div>

				<?php
				break;
			}

			case "template-view-out-email": {
				// =====================================================================
				// Template for viewing a sent email
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>

				Viewing sent mail here

				<?php
				break;
			}

			case "template-search": {
				// =====================================================================
				// Template for viewing search reuslts
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				if(!isset($_GET['q'])) {
					die("<h1>Query string not found</h1>");
				}
				else {
					$query = $_GET['q'];
				}

				$search_result = search::doSearch(ses_user_id, $query);

				?>

				<?php if($search_result['success']): ?>
					<?php
					foreach ($search_result['data']['results'] as $value) {
						switch($value['type']) {
							case "inbox": {
								/**
								* $value legend:
								* id = p1
								* sender = p2
								* subject = p3
								* has_seen = p4
								* is_sender_verified = p5
								*/
								?>
								<div
								class="mailitem <?= ($value['p4'] ? 'seen' : 'normal'); ?> noselect"
								onclick="Tab.changeTab('template-view-in-email', false, 'tab-body', 'id=<?= $value['p1'] ?>')">
									<?php if(!$value['p5']): ?>
										<div style="background-color:red;" class="circle" title="Unverified sender"></div>
									<?php endif; ?>

									<div class="user-container" title="Sender: <?= htmlentities($value['p2']); ?>">
										<span class="user-span">
											<?= htmlentities(str_smallify($value['p2'], 18)); ?>
										</span>
									</div>

									<div class="subject-container" title="Subject: <?= htmlentities($value['p3']); ?>">
										<span class="subject-span">
											<?= htmlentities(str_smallify($value['p3'], 70)); ?>
										</span>
									</div>

									<div class="date-container">
										<span class="date-span">
											<?= time::formatFromPresent($value['time']); ?>
										</span>
									</div>


								</div>
								<?php
								break;
							}

							case "vinbox": {
								/**
								* $value legend:
								* id = p1
								* sender = p2
								* subject = p3
								* has_seen = p4
								* vbox_id = p5
								*/
								?>
								<div class="mailitem <?= ($value['p4'] ? 'seen' : 'normal'); ?> noselect" onclick="Tab.changeTab('template-view-in-email', false, 'tab-body', 'id=<?= $value['p1'] ?>&vbox_id=<?= $value['p1'] ?>')">

									<div class="user-container" title="Sender: <?= htmlentities($value['p2']); ?>">
										<span class="user-span">
											[vBox]<?= htmlentities(str_smallify($value['p2'], 16)); ?>
										</span>
									</div>

									<div class="subject-container" title="Subject: <?= htmlentities($value['p3']); ?>">
										<span class="subject-span">
											<?= htmlentities(str_smallify($value['p3'], 70)); ?>
										</span>
									</div>

									<div class="date-container">
										<span class="date-span">
											<?= time::formatFromPresent($value['time']); ?>
										</span>
									</div>


								</div>
								<?php
								break;
							}

							case "outbox": {
								/**
								* $value legend:
								* id = p1
								* recipients = p2
								* subject = p3
								*/

								$recipients = json_decode($value['p2'], false);
								$recipients_count = count($recipients);

								?>
								<div class="mailitem normal noselect">
									<div class="user-container">

										<?php if($recipients_count > 1): ?>
											<span class="user-span" title="<?= htmlentities($recipients[0]); ?>">
												&lt;<?= htmlspecialchars(str_smallify($recipients[0], 13)) ?>&gt; and <?= ($recipients_count - 1); ?> other<?= ($recipients_count > 2 ? 's' : ''); ?>.
											</span>

										<?php else: ?>
											<span class="user-span" title="<?= htmlentities($recipients[0]); ?>">
												<?= htmlspecialchars(str_smallify($recipients[0], 28)); ?>
											</span>

										<?php endif; ?>

									</div>
									<div class="subject-container">
										<span class="subject-span"><?=  htmlspecialchars(str_smallify($value['p3'], 71)); ?></span>
									</div>
									<div class="date-container">
										<span class="date-span"><?= time::formatFromPresent($value['time']); ?></span>
									</div>
								</div>
								<?php
								break;
							}

							default: {
								break;
							}
						}
					}
					?>
				<?php else: ?>
					<h2 class="noselect"><?= htmlentities($search_result['data']['message']); ?></h2>
				<?php endif; ?>

				<?php
				break;
			}

			case "template-login-logs": {
				// =====================================================================
				// Template for viewing attempted logins
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$login_attempts = logs::getLoginLogs();
				$login_attempts_count = count($login_attempts);
				$technical_mode = preferences::getPreference('technical_mode');

				?>
				<div class="noselect" id="log-container">
					<!-- section -->
					<?php if($login_attempts !== false && $login_attempts_count > 0): ?>

						<div class="log-header">
							<span class="log-header-text">
								Login Attempts
							</span>
						</div>

						<div style="padding:5px;">
							<a href="<?= logs::getLogExporterRoute(); ?>" style="text-decoration:none;" target="_blank">Export</a>
						</div>

						<?php foreach($login_attempts as $login_attempt): ?>
							<?php
							$user_agent = new user_agent($login_attempt['user_agent']);
							$browser = $user_agent->getBrowser();
							$version = $user_agent->getVersion();
							$platform = $user_agent->getPlatform();
							?>
							<div class="log-row">
								<div class="column">
									<?= $login_attempt['login_successful'] ? 'Successful' : 'Failed' ?> login attempt from <?= time::formatFromPresent($login_attempt['date']); ?>
									<div style="margin-top: 14px"></div>
								</div>

								<div class="column">
									<div class="column-key">
										Country:
									</div>

									<div class="column-value">
										<?= geo::getCountry($login_attempt['ip']); ?>
									</div>
								</div>

								<div class="column">
									<div class="column-key">
										Browser:
									</div>

									<div class="column-value">
										<?= esc($browser); ?><?= $technical_mode ? '/'. $version : '' ?>
									</div>
								</div>

								<div class="column">
									<div class="column-key">
										Platform:
									</div>

									<div class="column-value">
										<?= esc($platform); ?>
									</div>
								</div>

								<?php if ($technical_mode): ?>
									<div class="column">
										<div class="column-key">
											IP Address:
										</div>

										<div class="column-value">
											<?= htmlentities($login_attempt['ip']); ?>
										</div>
									</div>

									<div class="column">
										<div class="column-key">
											User Agent:
										</div>

										<div class="column-value" title="<?= htmlentities($login_attempt['user_agent']); ?>">
											<?= htmlentities(str_smallify($login_attempt['user_agent'], 32)); ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<h2>No access logs found</h2>
					<?php endif; ?>
				</div>
				<?php

				break;
			}

			case "template-notifications": {
				// =====================================================================
				// Template for listening notification history
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$notifications = notifications::get();

				$last_month = '';
				$last_year = '';

				?>

				<?php if ($notifications !== false): ?>
					<div id="template-notifications">
						<?php foreach ($notifications as $notification): ?>
							<?php
							$month = date('F', $notification['date']);
							$year = date('Y', $notification['date']);
							?>


							<?php if ($year !== $last_year): ?>
								<div class="year">
									<h1 class="year-text">
										<?= htmlentities($year); ?>
									</h1>
								</div>
							<?php endif; ?>

							<?php if ($month !== $last_month || $year !== $last_year): ?>
								<div class="month">
									<h2 class="month-text">
										<?= htmlentities($month); ?>
									</h2>
								</div>
							<?php endif; ?>

							<div class="item">
								<a href="<?= notifications::getRedirectRoute($notification['id']); ?>" target="_blank">
									<?= htmlentities($notification['text']); ?>
								</a>
							</div>

							<?php
							$last_year = $year;
							$last_month = $month;
							?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<h2>No Notifications Found</h2>
				<?php endif; ?>

				<?php

				break;
			}

			case "template-general-settings": {
				// =====================================================================
				// Template for settings
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$has_password_history = user::hasPasswordHistory(ses_user_id);

				?>
				<div id="settings-tab">
					<div class="settings-tab-sidebar noselect">
						<div class="item">
							<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-preferences', 'settings-tab-body'); return false;">
								Preferences
							</a>
						</div>

						<div class="item" style="margin-left: 8px; margin-top: 14px;">
							<span>-- Profile --</span>
						</div>

						<div class="item">
							<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-profile-page', 'settings-tab-body'); return false;">
								Profile Page
							</a>
						</div>

						<div class="item">
							<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-profile-picture', 'settings-tab-body'); return false;">
								Profile Picture
							</a>
						</div>

						<div class="item" style="margin-left: 8px; margin-top: 14px;">
							<span>-- Security --</span>
						</div>

						<div class="item">
							<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-password', 'settings-tab-body'); return false;">
								Password
							</a>
						</div>

						<?php if ($has_password_history): ?>
							<div class="item">
								<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-password-history', 'settings-tab-body'); return false;">
									Password History
								</a>
							</div>
						<?php endif; ?>

						<!--<div class="item">
							<a class="text" href="javascript://" onclick="TemplateEngine.getAndSetTemplate('template-settings-preferences', 'settings-tab-body'); return false;">
								Google 2FA
							</a>
						</div>-->

					</div>
					<div id="settings-tab-body">
						<?php template::outputTemplate('template-settings-preferences', templateToken); ?>
					</div>
				</div>
				<?php

				break;
			}

			case "template-settings-preferences": {
				// =====================================================================
				// Template for the settings page, preferences
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$preferences = preferences::getPreferences(ses_user_id);
				$preference_options = preferences::getPreferenceOptions();

				?>
				<h2 class="noselect">Preferences</h2>

				<form class="preference-form" action="<?= post::getPostRoute('settings-preferences') ?>" method="POST">

					<?php foreach ($preference_options as $key => $value): ?>
						<div class="checkbox-input-container">
							<span class="text" title="<?= esc($value['descrption']); ?>">
								<input type="checkbox" name="<?= esc($key) ?>" <?= $preferences[$key] ? 'checked' : '' ?>> <?= esc($value['clean_name']); ?>
							</span>
						</div>
					<?php endforeach; ?>

					<div class="checkbox-input-container" style="margin-top: 30px;">
						<input class="button" type="submit" value="Save">
					</div>
				</form>
				<?php

				break;
			}

			case "template-settings-password": {
				// =====================================================================
				// Template for password page
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>
				<h2 class="noselect">Password Manager</h2>

				<div class="password-change-container">
					<div class="input-div">
						<div class="text-container">
							<span class="text">Password Verification</span>
						</div>
						<input type="password" class="clean-textbox" id="password-change-current" placeholder="Verify your password">
					</div>

					<div class="input-div">
						<div class="text-container">
							<span class="text">New password</span>
						</div>
						<input type="password" class="clean-textbox" id="password-change-new" placeholder="Enter a new password">
					</div>

					<div class="input-div">
						<div class="text-container">
							<span class="text">Repeat new password</span>
						</div>
						<input type="password" class="clean-textbox" id="password-change-verifiction" placeholder="Repeat your new password">
					</div>


					<input type="button" class="button" value="Submit" onclick="Auth.autoChangePassword(); return false;">
				</div>
				<?php

				break;
			}

			case "template-settings-password-history": {
				// =====================================================================
				// Template for password history page
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$technical_mode = preferences::getPreference('technical_mode');

				$cache_key = $cache->buildKey("ui-pw-history");
				$html_content = $cache->get($cache_key);

				if($html_content) {
					// Cached
					echo $html_content;
				}
				else {
					// Not cache_dir
					$password_history = user::getPasswordHistory();

					ob_start(function($buf) use(&$html_content) {
						$html_content .= $buf;
						return $buf;
					});

					?>
					<h2 class="noselect">Password History</h2>

					<div class="password-history">
						<?php if ($password_history['success']): ?>
							<?php foreach ($password_history['data'] as $history_index): ?>
								<?php
								$user_agent = new user_agent($history_index['user_agent']);
								$browser = $user_agent->getBrowser();
								$browser_version = $user_agent->getVersion();
								$platform = $user_agent->getPlatform();
								?>
								<div class="password-item">
									<!-- <?= $history_index['__index__'] ?> -->
									<div>
										Set <span><?= esc(time::formatFromPresent($history_index['date'])) ?></span>
									</div>

									<div style="margin-top: 8px;"></div>

									<div>
										 Country: <span class="text"><?= esc(geo::getCountry($history_index['ip'])) ?></span>
									</div>

									<div>
										Bowser: <span class="text"><?= esc($browser) ?><?= $technical_mode ? '/'. esc($browser_version) : '' ?></span>
									</div>

									<?php if ($platform !== null && strlen($platform) > 1): ?>
										<div>
											Platform: <span class="text"><?= esc($platform) ?></span>
										</div>

									<?php endif; ?>

									<?php if ($technical_mode): ?>
										<div style="margin-top: 5px;"></div>

										<div>
											IP Address (Technical): <span class="text"><?= esc($history_index['ip']) ?></span>
										</div>

										<div>
											User Agent (Technical): <span class="text noselect" title="<?= esc($history_index['user_agent']) ?>"><?= esc(str_smallify($history_index['user_agent'], 25)) ?></span>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<h3 lass="noselect">No Password Chanes Found</h3>
						<?php endif; ?>
					</div>
					<?php

					// Getting the content.
					ob_end_clean();

					// Storing this in cache
					$cache->store($cache_key, $html_content);
				}


				break;
			}

			case "template-settings-profile-page": {
				// =====================================================================
				// Template for profile page settings
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>
				<h2 class="noselect">Porofile page settings</h2>
				<?php

				break;
			}

			case "template-settings-profile-picture": {
				// =====================================================================
				// Template for password page
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>
				<h2 class="noselect">Profile Picture Settings Page</h2>
				<?php

				break;
			}

			case "template-virtual-emails": {
				// =====================================================================
				// Mail template for virtual mails
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$vboxes = vmailbox::getVBoxes();
				$can_create = vmailbox::canCreate();

				?>
				<div class="noselect" id="virtual-email-sidebar">
					<div class="item">
						<a class="text" onclick="TemplateEngine.getAndSetTemplate('template-virtual-info', 'virtual-email-body')">
							Info
						</a>
					</div>

					<?php if ($can_create): ?>
						<div class="item">
							<a class="text" onclick="TemplateEngine.getAndSetTemplate('template-virtual-create', 'virtual-email-body')">
								Create New
							</a>
						</div>
					<?php endif; ?>

					<?php if ($vboxes !== false): ?>
						<div style="margin-top:10px;border-top-width:1px;border-top-style:solid;width:80%;border-top-color:#b3b3b3;"></div>

						<?php foreach ($vboxes as $vbox): ?>
							<?php
							$vbox_unread_count = vmailbox::getVBoxInboxUnreadCount($vbox['id']);
							?>
							<div class="item">
								<span style="cursor:pointer;" onclick="TemplateEngine.getAndSetTemplate('template-virtual-manage', 'virtual-email-body', 'id=<?= $vbox['id']; ?>')" title="Manage">
									<img src="<?= assetloader::getInlineImage('setting-20', 'png') ?>" alt="[-]" height="16px" />
								</span>
								<a class="text" onclick="Tab.changeTab('template-inbox', 'tab-inbox', 'tab-body', 'vbox_id=<?= $vbox['id']; ?>')">
									<?php if ($vbox['is_enabled']): ?>
										<?php if ($vbox_unread_count > 0): ?>
											<?= htmlentities(str_smallify(misc::constructAddress($vbox['username']), 20)); ?>
											<span style="color:#717171;font-size:11px;">[<?= $vbox_unread_count ?>]</span>
										<?php else: ?>
											<?= htmlentities(str_smallify(misc::constructAddress($vbox['username']), 20)); ?>
										<?php endif; ?>
									<?php else: ?>
										<span style="text-decoration:line-through"><?= htmlentities(str_smallify(misc::constructAddress($vbox['username']), 20)); ?></span>
									<?php endif; ?>
								</a>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<div id="virtual-email-body">
					<?php template::outputTemplate('template-virtual-info', templateToken); ?>
				</div>
				<?php

				break;
			}

			case "template-virtual-create": {
				// =====================================================================
				// Template for creating new virtual email address
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>

				<div id="virtual-email-new-container">
					<div class="input-container">
						<input type="text" id="vbox-username" class="input-text" maxlength="64" autofocus><span class="domain-text noselect">@<?= htmlentities(config['mailDomain']); ?></span>
					</div>

					<div class="noselect" id="virtual-email-new-status-container" hidden>
						<span id="virtual-email-new-status">
							Status
						</span>
					</div>

					<input type="button" class="button" value="Create" onclick="vmail.autoCreate();">
				</div>

				<?php

				break;
			}

			case "template-virtual-manage": {
				// =====================================================================
				// Template for managing virtual email address
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				$id = 0;
				if(!isset($_GET['id'])) {
					die('Unknown ID');
				}
				$id = $_GET['id'];

				$vbox_info = vmailbox::getVBoxInfo($id);
				$vbox_inbox_count = vmailbox::getVBoxInboxCount($id);

				?>

				<?php if($vbox_info !== false): ?>
					<div id="virtual-email-info-container">

						<div class="virtual-email-info-item-container">
							<div class="name">
								<span class="text">
									Address:
								</span>
							</div>

							<div class="value">
								<span class="text">
									<?= htmlentities(misc::constructAddress($vbox_info['username'])); ?>
								</span>
							</div>
						</div>

						<div class="virtual-email-info-item-container">
							<div class="name">
								<span class="text">
									Inbox Size:
								</span>
							</div>

							<div class="value">
								<span class="text">
									<?= $vbox_inbox_count; ?>
								</span>
							</div>
						</div>
					</div>

					<div id="virtual-email-enable-container">
						<?php if ($vbox_info['is_enabled']): ?>
							<input type="button" value="Disable" class="vmail-disable-button" onclick="vbox.disableMailbox(<?= intval($id); ?>)">
						<?php else: ?>
							<input type="button" value="Enable" class="vmail-enable-button" onclick="vbox.enableMailbox(<?= intval($id); ?>)">
						<?php endif; ?>
					</div>

				<?php else: ?>
					<h2>Unable to get mailbox information</h2>
				<?php endif; ?>

				<?php

				break;
			}

			case "template-virtual-info": {
				// =====================================================================
				// Template for viewing information about virtual emails
				// =====================================================================

				if(!ses_logged_in) {
					return false;
				}

				if(ses_awaiting_security_check) {
					return false;
				}

				?>
				<h2 class="noselect">What is a vMail?</h2>
				<p class="noselect">
					vMail, sometimes refereed to as a virtual mailbox, is a mailbox
					seperated from that main mailbox. This way you can receive mail to
					a virtual address rather then your main. You can also disable, and
					reenable virtual mailboxes at anytime.
				</p>

				<div style="margin-top:50px;"></div>

				<h2 class="noselect">Why use vMail?</h2>
				<p class="noselect">
					If you signup to a lot of new websites, you often require email
					verification. Rather then exposing your main email address, you can
					put a virtual email address. This way you will not get irritating updates
					in your main mailbox, or any other types unneeded mail.
				</p>
				<?php

				break;
			}



			// =======================================================================
			// Authentication page.
			// =======================================================================

			case "template-login": {
				// =====================================================================
				// Template for logging in
				// =====================================================================

				if(ses_logged_in) {
					return false;
				}

				?>
				<div id="template-login">
					<h1 class="noselect">Login</h1>

					<div class="input-div">
						<div class="span-container">
							<span class="span input-span">Username</span>
						</div>

						<input id="login-username" class="clean-textbox" type="text" placeholder="Username" autofocus></input>
					</div>


					<div class="input-div">
						<div class="span-container">
							<span class="span input-span">Password</span>
						</div>

						<input id="login-password" class="clean-textbox" type="password" placeholder="Password"></input>
					</div>

					<div id="misc-container">
						<div class="sub-container">
							<code class="noselect">
								<span id="auth-status" hidden>
								</span>
							</code>
						</div>

						<div class="sub-container">
							<small>
								<span class="span">Need an account? <a href="#register" onclick="TemplateEngine.getAndSetTemplate('template-register', 'template-container');">Click Here</a></span>
							</small>
						</div>
					</div>

					<button onclick="Auth.autoLogin();" class="button">Submit</button>
					<a href="<?= router::instance()->getRoutePath('landing'); ?>" class="button" style="font-size: 13px;">Return Home</a>
				</div>
				<?php
				break;
			}

			case "template-register": {
				// =====================================================================
				// Template for registering
				// =====================================================================

				if(ses_logged_in) {
					return false;
				}

				?>
				<div id="template-login">
					<h1 class="noselect">Registration</h1>

					<div class="input-div">
						<!-- Username -->
						<div class="span-container">
							<span class="span input-span">Username</span>
						</div>

						<input id="register-username" class="clean-textbox" type="text" placeholder="Username" autofocus required></input>
					</div>

					<div class="input-div">
						<!-- Password -->
						<div class="span-container">
							<span class="span input-span">Password</span>
						</div>

						<input id="register-password" class="clean-textbox" type="password" placeholder="Password" required></input>
					</div>

					<div class="input-div">
						<!-- Password -->
						<div class="span-container">
							<span class="span input-span">Full Name</span>
						</div>

						<input id="register-first-name" class="clean-textbox" type="text" placeholder="First name" required></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-last-name" class="clean-textbox" type="text" placeholder="Last name" required></input>
					</div>

					<div class="input-div">
						<!-- Security Questions -->
						<div class="span-container">
							<span class="span input-span">Security Question</span>
						</div>

						<input id="register-question" class="clean-textbox" type="text" placeholder="Question" title="Example: What's your mothers name?" required></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-answer" class="clean-textbox" type="text" placeholder="Answer" title="Example: Katrina" required></input>
						<div style="margin-top: 5px;"></div>
						<input id="register-hint" class="clean-textbox" type="text" placeholder="Hint" title="Example: Name starting with a K" required></input>
					</div>

					<div id="misc-container">
						<div class="sub-container">
							<code class="noselect">
								<span id="auth-status" hidden></span>
							</code>
						</div>

						<div class="sub-container">
							<small>
								<span class="span">Already have an account? <a href="#" onclick="TemplateEngine.getAndSetTemplate('template-login', 'template-container');">Click Here</a></span>
							</small>
						</div>
					</div>

					<button onclick="Auth.autoRegister();" class="button">Submit</button>
					<a href="<?= router::instance()->getRoutePath('landing'); ?>" class="button" style="font-size: 13px;">Return Home</a>
				</div>
				<?php
				break;
			}



			default: {

				return false;
				break;
			}
		}

		return true;
	}
}
