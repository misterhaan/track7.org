<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'loadprofile':
			if($user->IsLoggedIn()) {
				$ajax->Data->username = $user->Username;
				$ajax->Data->displayname = $user->DisplayName == $user->Username ? '' : $user->DisplayName;
			} else
				$ajax->Fail('unable to load profile because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'saveprofile':
			if($user->IsLoggedIn())
				if(isset($_POST['username']))
					if(true === $msg = t7user::CheckUsername($_POST['username'] = trim($_POST['username']), +$user->ID)) {
						$_POST['displayname'] = trim($_POST['displayname']);
						if($_POST['displayname'] == $_POST['username'] || true !== t7user::CheckName($_POST['displayname'], +$user->ID))
							$_POST['displayname'] = '';
						if($db->real_query('update users set username=\'' . $db->escape_string($_POST['username']) . '\', displayname=\'' . $db->escape_string($_POST['displayname']) . '\' where id=\'' . +$user->ID . '\' limit 1')) {
							$ajax->Data->username = $_POST['username'];
							$ajax->Data->displayname = $_POST['displayname'] ? $_POST['displayname'] : $_POST['username'];
						} else
							$ajax->Fail('error updating database.');
					} else
						$ajax->Fail($msg);
				else
					$ajax->Fail('username is required.');
			else
				$ajax->Fail('unable to save profile because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'loadtime':
			if($user->IsLoggedIn()) {
				$ajax->Data->currenttime = t7format::LocalDate('g:i a', time());
				$ajax->Data->dst = $user->DST;
			} else
				$ajax->Fail('unable to load time settings because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'savetime':
			if($user->IsLoggedIn()) {
				if(isset($_POST['currenttime']) && isset($_POST['dst'])) {
					$_POST['dst'] = $_POST['dst'] == 'true';
					$st = time();
					$lt = $_POST['dst'] ? strtotime(trim($_POST['currenttime'])) : strtotime(trim($_POST['currenttime']) . ' GMT');
					$offset = round(($lt - $st) / 900) * 900;
					if($db->real_query('update users_settings set timebase=\'' . ($_POST['dst'] ? 'server' : 'gmt') . '\', timeoffset=\'' . +$offset . '\' where id=\'' . +$user->ID . '\' limit 1')) {
						;  // success is default
					} else
						$ajax->Fail('error updating database.');
				} else
					$ajax->Fail('did not receive new time settings, so nothing to do.');
			} else
				$ajax->Fail('unable to save time settings because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'loadcontact':
			if($user->IsLoggedIn()) {
				$contact = 'select e.email, e.vis_email, p.website, p.vis_website, p.twitter, p.vis_twitter, p.google, p.vis_google, p.facebook, p.vis_facebook, p.steam, p.vis_steam';
				if($contact = $db->query($contact . ' from users_email as e left join users_profiles as p on p.id=e.id where e.id=\'' . +$user->ID . '\' union all ' . $contact . ' from users_email as e right join users_profiles as p on p.id=e.id where e.id is null and p.id=\'' . +$user->ID . '\'')) {
					if($contact = $contact->fetch_object())
						$ajax->Data = $contact;
					else
						$ajax->Data = (object)['email' => '', 'vis_email' => 'none', 'website' => '', 'vis_website' => 'all', 'twitter' => '', 'vis_twitter' => 'friends', 'google' => '', 'vis_google' => 'friends', 'facebook' => '', 'vis_facebook' => 'friends', 'steam' => '', 'vis_steam' => 'friends'];
				} else
					$ajax->Fail('error looking up contact information.');
			} else
				$ajax->Fail('unable to load contact information because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'checkurl':
			if(isset($_GET['url'])) {
				$url = trim($_GET['url']);
				if(t7format::CheckUrl($url)) {
					if($url != $_GET['url'])
						$ajax->Data->replace = $url;
				} else
					$ajax->Fail('invalid url.  please enter a url to a reachable web page.');
			} else
				$ajax->Fail('url missing.');
			break;
		case 'checktwitter':
			if(isset($_GET['twitter'])) {
				$twitter = trim($_GET['twitter']);
				if(CheckTwitter($twitter)) {
					if($twitter != $_GET['twitter'])
						$ajax->Data->replace = $twitter;
				} else
					$ajax->Fail('invalid twitter username.  please enter your twitter username or the url to your twitter profile.');
			} else
				$ajax->Fail('twitter missing.');
			break;
		case 'checkgoogle':
			if(isset($_GET['google'])) {
				$google = trim($_GET['google']);
				if(CheckGoogle($google)) {
					if($google != $_GET['google'])
						$ajax->Data->replace = $google;
				} else
					$ajax->Fail('invalid google profile.  please enter your google plus name (with the +) or the url to your google plus profile.');
			} else
				$ajax->Fail('google missing.');
			break;
		case 'checkfacebook':
			if(isset($_GET['facebook'])) {
				$facebook = trim($_GET['facebook']);
				if(CheckFacebook($facebook)) {
					if($facebook != $_GET['facebook'])
						$ajax->Data->replace = $facebook;
				} else
					$ajax->Fail('invalid facebook profile.  please enter your facebook username or the url to your facebook profile.');
			} else
				$ajax->Fail('facebook missing.');
			break;
		case 'checksteam':
			if(isset($_GET['steam'])) {
				$steam = trim($_GET['steam']);
				if(CheckSteam($steam)) {
					if($steam != $_GET['steam'])
						$ajax->Data->replace = $steam;
				} else
					$ajax->Fail('invalid steam profile.  please enter your steam custom url or the url to your steam community profile.');
			} else
				$ajax->Fail('steam missing.');
			break;
		case 'savecontact':
			if($user->IsLoggedIn())
				if(isset($_POST['email'], $_POST['vis_email'], $_POST['website'], $_POST['vis_website'], $_POST['twitter'], $_POST['vis_twitter'], $_POST['google'], $_POST['vis_google'], $_POST['facebook'], $_POST['vis_facebook'], $_POST['steam'], $_POST['vis_steam']))
					if(strtolower(substr($_POST['email'] = trim($_POST['email']), -12)) != '@example.com')
						if($_POST['email'] == '' || t7user::CheckEmail($_POST['email']))
							if(CheckEmailVisibility($_POST['vis_email']))
								if('' == ($_POST['website'] = trim($_POST['website'])) || t7format::CheckUrl($_POST['website']))
									if(CheckContactVisibilty($_POST['vis_website']))
										if('' == ($_POST['twitter'] = trim($_POST['twitter'])) || CheckTwitter($_POST['twitter']))
											if(CheckContactVisibilty($_POST['vis_twitter']))
												if('' == ($_POST['google'] = trim($_POST['google'])) || CheckGoogle($_POST['google']))
													if(CheckContactVisibilty($_POST['vis_google']))
														if('' == ($_POST['facebook'] = trim($_POST['facebook'])) || CheckFacebook($_POST['facebook']))
															if(CheckContactVisibilty($_POST['vis_facebook']))
																if('' == ($_POST['steam'] = trim($_POST['steam'])) || CheckSteam($_POST['steam']))
																	if(CheckContactVisibilty($_POST['vis_steam'])) {
																		$update = 'insert into users_email (id, email, vis_email) values (\''. +$user->ID . '\', \''
																				. $db->escape_string($_POST['email']) . '\', \''
																				. $db->escape_string($_POST['vis_email']) . '\') on duplicate key update email=\''
																				. $db->escape_string($_POST['email']) . '\', vis_email=\''
																				. $db->escape_string($_POST['vis_email']) . '\'';
																		if($db->real_query($update)) {
																			$update = 'insert into users_profiles (id, website, vis_website, twitter, vis_twitter, google, vis_google, facebook, vis_facebook, steam, vis_steam) values (\''. +$user->ID . '\', \''
																					. $db->escape_string($_POST['website']) . '\', \''
																					. $db->escape_string($_POST['vis_website']) . '\', \''
																					. $db->escape_string($_POST['twitter']) . '\', \''
																					. $db->escape_string($_POST['vis_twitter']) . '\', \''
																					. $db->escape_string($_POST['google']) . '\', \''
																					. $db->escape_string($_POST['vis_google']) . '\', \''
																					. $db->escape_string($_POST['facebook']) . '\', \''
																					. $db->escape_string($_POST['vis_facebook']) . '\', \''
																					. $db->escape_string($_POST['steam']) . '\', \''
																					. $db->escape_string($_POST['vis_steam']) . '\') on duplicate key update website=\''
																					. $db->escape_string($_POST['website']) . '\', vis_website=\''
																					. $db->escape_string($_POST['vis_website']) . '\', twitter=\''
																					. $db->escape_string($_POST['twitter']) . '\', vis_twitter=\''
																					. $db->escape_string($_POST['vis_twitter']) . '\', google=\''
																					. $db->escape_string($_POST['google']) . '\', vis_google=\''
																					. $db->escape_string($_POST['vis_google']) . '\', facebook=\''
																					. $db->escape_string($_POST['facebook']) . '\', vis_facebook=\''
																					. $db->escape_string($_POST['vis_facebook']) . '\', steam=\''
																					. $db->escape_string($_POST['steam']) . '\', vis_steam=\''
																					. $db->escape_string($_POST['vis_steam']) . '\'';
																			if(!$db->real_query($update))
																				$ajax->Fail('error saving external profile links.');
																		} else
																			$ajax->Fail('error saving email address.');
																	} else
																		$ajax->Fail('invalid steam visibility.  trust no one.');
																else
																	$ajax->Fail('unable to figure out what you meant for steam.  please enter the url to your profile.');
															else
																$ajax->Fail('invalid facebook visibility.  trust no one.');
														else
															$ajax->Fail('unable to figure out what you meant for facebook.  please enter your username or the url to your profile.');
													else
														$ajax->Fail('invalid google visibility.  trust no one.');
												else
													$ajax->Fail('unable to figure out what you meant for google.  please enter your google plus name or numeric google id.');
											else
												$ajax->Fail('invalid twitter visibility.  trust no one.');
										else
											$ajax->Fail('unable to figure out what you meant for twitter.  please enter your twitter username or leave it blank.');
									else
										$ajax->Fail('invalid website visibility.  trust no one.');
								else
									$ajax->Fail('cannot validate website.  please enter a valid url to a working website.');
							else
								$ajax->Fail('invalid e-mail visibility.  trust no one.');
						else
							$ajax->Fail('e-mail address doesn’t look like an e-mail address.');
					else
						$ajax->Fail('e-mail address is not required, please don’t enter a fake one.');
				else
					$ajax->Fail('required fields not present.');
			else
				$ajax->Fail('unable to save contact information because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'loadnotification':
			if($user->IsLoggedIn())
				if($notif = $db->query('select e.email, coalesce(s.emailnewmsg, 1) as emailnewmsg from users as u left join users_email as e on e.id=u.id left join users_settings as s on s.id=u.id where u.id=\'' . +$user->ID . '\' limit 1'))
					if($notif = $notif->fetch_object()) {
						$notif->emailnewmsg = $notif->emailnewmsg == true;
						$ajax->Data = $notif;
					} else
						$ajax->Data = (object)['email' => '', 'emailnewmsg' => true];
				else
					$ajax->Fail('error looking up notification settings.');
			else
				$ajax->Fail('unable to load notification settings because you are not signed in.  this can happen if you have left the page open for too long.');
				break;
		case 'savenotification':
			if($user->IsLoggedIn())
				if(isset($_POST['notifymsg']))
					if($db->query('insert into users_settings (id, emailnewmsg) values (\'' . +$user->ID . '\', \'' . +$_POST['notifymsg'] . '\') on duplicate key update emailnewmsg=\'' . +$_POST['notifymsg'] . '\''))
						;  // nothing to send back
					else
						$ajax->Fail('error saving notification settings.');
				else
					$ajax->Fail('required field not present.');
			else
				$ajax->Fail('unable to save notification settings because you are not signed in.  this can happen if you have left the page open for too long.');
			break;
		case 'removetransition':
			if($user->IsLoggedIn())
				if(UserHasSecureLogin()) {
					if(!$db->real_query('delete from transition_login where id=\'' . +$user->ID . '\''))
						$ajax->Fail('unable to remove old login because something went wrong with the database.');
				} else
					$ajax->Fail('unable to remove old login because it is your only way of signing in.  link another account for sign in and try again.');
			else
				$ajax->Fail('unable to remove old login because you are not signed in.  most likely your session has expired because it’s been too long since you’ve done anything, in which case you need to sign in again.');
			break;
		case 'removeaccount':
			if($user->IsLoggedIn())
				if(UserHasMultipleLogins())
					if(isset($_POST['source']) && isset($_POST['id']) && in_array($_POST['source'], ['google', 'twitter', 'facebook'])) {
						if(!$db->real_query('delete from login_' . $_POST['source'] . ' where id=\'' . +$_POST['id'] . '\' and user=\'' . +$user->ID . '\''))
							$ajax->Fail('unable to remove sign-in account because something went wrong with the database.');
					} else
						$ajax->Fail('invalid or missing account type and id.');
				else
					$ajax->Fail('unable to remove sign-in account because it is your only secure way of signing in.  link another account for sign in and try again.');
			else
				$ajax->Fail('unable to remove sign-in account because you are not signed in.  most likely your session has expired because it’s been too long since you’ve done anything, in which case you need to sign in again.');
			break;
		default:
			$ajax->Fail('unknown function name.  supported function names are: loadprofile, saveprofile, loadtime, savetime, loadcontact, checkurl, checktwitter, checkgoogle, checkfacebook, checksteam, savecontact, loadnotification, savenotification, removetransition, removeaccount.');
			break;
	}
	$ajax->Send();
	die();
}

$html = new t7html([]);
$html->Open('settings');
?>
			<h1>settings</h1>
<?php

if(!$user->IsLoggedIn()) {
?>
			<p>
				to change your settings, we need to know who you are. sign in and tweak
				track7 just how you like it.
			</p>
<?php
	$html->Close();
	die();
}
?>
			<div class=tabbed>
				<nav class=tabs>
					<a href=#profile title="name and avatar">profile</a>
					<a href=#timezone title="configure times to display for where you are">time zone</a>
					<a href=#contact title="e-mail and profiles on other sites">contact</a>
					<a href=#notification title="choose when track7 should notify you">notification</a>
					<a href=#linkedaccounts title="manage which accounts you use to sign in to track7">sign-in accounts</a>
				</nav>

				<form class=tabcontent id=profile>
					<label title="used in the url to your profile">
						<span class=label>username:</span>
						<span class=field><input id=username></span>
						<span class="validation checking" title=""></span>
					</label>
					<label title="an easier to read name for when you comment, etc.  leave blank to just show your username">
						<span class=label>display name:</span>
						<span class=field><input id=displayname></span>
						<span class="validation checking" title=""></span>
					</label>
					<button class=save>save</button>
				</form>

				<form class=tabcontent id=timezone>
					<label>
						<span class=label>current time:</span>
						<input id=currenttime>
						<button id=detecttime title="detect the current time from your computer / tablet / phone">detect</button>
					</label>
					<label><input type=checkbox id=dst> use daylight saving time</label>
					<button class=save>save</button>
				</form>

				<form class=tabcontent id=contact>
					<p>
						while anyone can send you a message on track7, you can also provide
						other contact information here and control who can see it.  you must
						provide an e-mail address if you want track7 to e-mail you (like
						when someone sends you a message), but you can set it not to display
						to anyone.  all of this information is optional.
					</p>
					<label title="address at which track7 and (if you choose) users and / or visitors can contact you">
						<span class=label>e-mail:</span>
						<span class=field>
							<input type=email id=email maxlength=64>
							<a class="visibility droptrigger" id=vis_email data-value=none title="shown to nobody" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=none>nobody</a>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=users>signed-in users</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<label title="url of your personal website">
						<span class=label>website:</span>
						<span class=field>
							<input type=url id=website maxlength=64>
							<a class="visibility droptrigger" id=vis_website data-value=friends title="shown to my track7 friends" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<label title="your twitter username or twitter profile url">
						<span class=label>twitter:</span>
						<span class=field>
							<input id=twitter>
							<a class="visibility droptrigger" id=vis_twitter data-value=friends title="shown to my track7 friends" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<label title="google+ profile url">
						<span class=label>google:</span>
						<span class=field>
							<input id=google>
							<a class="visibility droptrigger" id=vis_google data-value=friends title="shown to my track7 friends" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<label title="facebook profile url">
						<span class=label>facebook:</span>
						<span class=field>
							<input id=facebook>
							<a class="visibility droptrigger" id=vis_facebook data-value=friends title="shown to my track7 friends" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<label title="steam profile url">
						<span class=label>steam:</span>
						<span class=field>
							<input id=steam>
							<a class="visibility droptrigger" id=vis_steam data-value=friends title="shown to my track7 friends" href=#visibility></a>
							<span class=droplist>
								<a class=visibility data-value=friends>my track7 friends</a>
								<a class=visibility data-value=all>everyone</a>
							</span>
						</span>
						<span class=validation></span>
					</label>
					<button class=save>save</button>
				</form>

				<form class=tabcontent id=notification>
					<label>
						<span class=label>e-mail:</span>
						<span class=field><span><span id=emaillabel></span> (change this in the contact section)</span></span>
					</label>
					<label>
						<span class=label>messages:</span>
						<span class="field checkbox"><input type=checkbox id=notifymsg> notify me by e-mail when someone sends me a message</span>
					</label>
					<button class=save>save</button>
					<p>
						another way to keep up-to-date with track7 is to subscribe to an
						<a href="/feeds/">rss feed</a> or follow
						<a href="https://twitter.com/track7feed">@track7feed</a> on twitter.
						these include the information on the track7 front page; there is no
						feed for your messages so you will need to enable e-mail
						notifications or visit the site to know when someone has sent you a
						message.  some sections of track7 have an orange rss icon at the top
						linked to a feed for just that section’s content.
					</p>
				</form>

				<form class=tabcontent id=linkedaccounts>
					<h2>linked accounts</h2>
					<div class="linkedaccounts">
<?php
$logins = 0;
if($transition = $db->query('select login from transition_login where id=\'' . +$user->ID . '\' limit 1'))
	if($transition = $transition->fetch_object())
		$logins++;
if($googles = $db->query('select l.id, p.name, p.url, ifnull(nullif(p.avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar from login_google as l left join external_profiles as p on p.id=l.profile where l.user=\'' . +$user->ID . '\''))
	$logins += $googles->num_rows;
if($twitters = $db->query('select l.id, p.name, p.url, ifnull(nullif(p.avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar from login_twitter as l left join external_profiles as p on p.id=l.profile where user=\'' . +$user->ID . '\''))
	$logins += $twitters->num_rows;
if($facebooks = $db->query('select l.id, p.name, p.url, ifnull(nullif(p.avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar from login_facebook as l left join external_profiles as p on p.id=l.profile where user=\'' . +$user->ID . '\''))
	$logins += $facebooks->num_rows;
// TODO: look up other methods
if($transition) {
?>
						<div class="linkedaccount transition">
							<img class=accounttype src="via/track7.png" alt=track7>
<?php
	if($logins > 1) {
?>
							<a href=#removetransition title="remove this login option and delete password information from track7">remove</a>
							<p class=securitynote>
								this login method is less secure because track7 isn’t encrypted.
								once you have verified your ability to sign in with one of the
								other accounts listed here, you should remove the username +
								password option.
							</p>
<?php
	} else {
?>
							<p class=securitynote>
								this login method is less secure because track7 isn’t encrypted.
								you should link another account for login and remove your track7
								username + password, especially if you use the same password
								elsewhere.
							</p>
<?php
	}
?>
						</div>
<?php
}
if($googles)
	while($google = $googles->fetch_object()) {
?>
						<div class="linkedaccount google">
							<a href="<?php echo htmlspecialchars($google->url); ?>" title="view the <?=$google->name; ?> profile on google"><img src="<?=$google->avatar; ?>"></a>
							<div class=actions>
<?php
		if($logins > 2 || $logins > 1 && !$transition) {
?>
								<a class=unlink href="#removeaccount" data-source=google data-id=<?php echo $google->id; ?> title="unlink this account so it can no longer be used to sign in to track7"></a>
<?php
		}
?>
							</div>
						</div>
<?php
	}
if($twitters)
	while($twitter = $twitters->fetch_object()) {
?>
						<div class="linkedaccount twitter">
							<a href="<?php echo htmlspecialchars($twitter->url); ?>" title="view the <?=$twitter->name; ?> profile on twitter"><img src="<?=$twitter->avatar; ?>"></a>
							<div class=actions>
<?php
		if($logins > 2 || $logins > 1 && !$transition) {
?>
								<a class=unlink href="#removeaccount" data-source=twitter data-id=<?php echo $twitter->id; ?> title="unlink this account so it can no longer be used to sign in to track7"></a>
<?php
		}
?>
							</div>
						</div>
<?php
	}
if($facebooks)
	while($facebook = $facebooks->fetch_object()) {
		?>
						<div class="linkedaccount facebook">
							<a href="<?php echo htmlspecialchars($facebook->url); ?>" title="view the <?=$facebook->name; ?> profile on facebook"><img src="<?=$facebook->avatar; ?>"></a>
							<div class=actions>
<?php
		if($logins > 2 || $logins > 1 && !$transition) {
?>
								<a class=unlink href="#removeaccount" data-source=facebook data-id=<?php echo $facebook->id; ?> title="unlink this account so it can no longer be used to sign in to track7"></a>
<?php
		}
?>
							</div>
						</div>
<?php
	}
?>
					</div>

					<h2>authorize another account</h2>
					<p>
						you currently have <?php echo $logins . ' way'; if($logins != 1) echo 's'; ?>
						to sign in to track7, but you can always add another. choose an
						account provider below to sign in and add it as a track7 sign in.
					</p>
					<div id=authchoices>
<?php
$auths = t7auth::GetAuthLinks($_SERVER['PHP_SELF'] . '#linkedaccounts', true);
foreach($auths as $name => $authurl) {
?>
						<a href="<?=htmlspecialchars($authurl); ?>" class=<?=$name; ?> title="link your <?=$name; ?> account for sign in"></a>
<?php
}
?>
					</div>
				</form>
			</div>
<?php
$html->Close();

function CheckTwitter(&$value) {
	$value = t7user::CollapseProfileLink($value, 'twitter');
	return preg_match('/^[A-Za-z0-9_]{1,15}$/', $value);
}

function CheckGoogle(&$value) {
	$value = t7user::CollapseProfileLink($value, 'google');
	return true;  // TODO:  some verification probably
}

function CheckFacebook(&$value) {
	$value = t7user::CollapseProfileLink($value, 'facebook');
	return preg_match('/^[A-Za-z0-9\.]{5,}$/', $value);
}

function CheckSteam(&$value) {
	$value = t7user::CollapseProfileLink($value, 'steam');
	return true;  // TODO:  some verification probably
}

function CheckEmailVisibility($value) {
	return in_array($value, array('none', 'friends', 'users', 'all'));
}

function CheckContactVisibilty($value) {
	return in_array($value, array('friends', 'all'));
}

function UserHasSecureLogin() {
	global $user;
	global $db;
	if($google = $db->query('select id from login_google where user=\'' . +$user->ID . '\''))
		if($google->num_rows)
			return true;
	if($twitter = $db->query('select id from login_twitter where user=\'' . +$user->ID . '\''))
		if($twitter->num_rows)
			return true;
	if($facebook = $db->query('select id from login_facebook where user=\'' . +$user->ID . '\''))
		if($facebook->num_rows)
			return true;
	// TODO: check other login methods
	return false;
}

function UserHasMultipleLogins() {
	global $user;
	global $db;
	$logins = 0;
	if($google = $db->query('select id from login_google where user=\'' . +$user->ID . '\''))
		if(($logins += $google->num_rows) > 1)
			return true;
	if($twitter = $db->query('select id from login_twitter where user=\'' . +$user->ID . '\''))
		if(($logins += $twitter->num_rows) > 1)
			return true;
	if($facebook = $db->query('select id from login_facebook where user=\'' . +$user->ID . '\''))
		if(($logins += $facebook->num_rows) > 1)
			return true;
	// TODO: check other login methods
	return false;
}
