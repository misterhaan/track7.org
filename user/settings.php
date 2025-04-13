<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html([]);
$html->Open('settings');
?>
<h1>settings</h1>
<?php

if (!$user->IsLoggedIn()) {
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
		<a href=#profile title="name and picture">profile</a>
		<a href=#timezone title="configure times to display for where you are">time zone</a>
		<a href=#contact title="e-mail and profiles on other sites">contact</a>
		<a href=#notification title="choose when track7 should notify you">notification</a>
		<a href=#linkedaccounts title="manage which accounts you use to sign in to track7">logins<?php if ($user->SettingsAlerts) echo '<span class=notifycount>' . $user->SettingsAlerts . '</span>'; ?></a>
	</nav>

	<form class=tabcontent id=profile>
		<label title="used in the url to your profile">
			<span class=label>username:</span>
			<span class=field><input id=username name=username></span>
			<span class="validation"></span>
		</label>
		<label title="an easier to read name for when you comment, etc.  leave blank to just show your username">
			<span class=label>display name:</span>
			<span class=field><input id=displayname name=displayname></span>
			<span class="validation"></span>
		</label>
		<fieldset class=avatar>
			<legend>profile picture:</legend>
			<label>
				<span class=field><input name=avatar value=current type=radio checked><img src="<?= $user->Avatar; ?>" class=avatar>no changes</span>
			</label>
			<label>
				<span class=field><input name=avatar value=none type=radio><img src="<?= t7user::DEFAULT_AVATAR; ?>" class=avatar>default anonymous picture</span>
			</label>
			<?php
			if ($email = $db->query('select contact as email from contact where user=' . +$user->ID . ' and type=\'email\' limit 1'))
				if ($email = $email->fetch_object())
					if ($email->email) {
			?>
				<label>
					<span class=field><input name=avatar value=gravatar type=radio><img src="https://www.gravatar.com/avatar/<?= md5(strtolower(trim($email->email))); ?>?s=128&d=robohash" class=avatar><span><a href="https://gravatar.com/">gravatar</a> for <?= $email->email; ?></span></span>
				</label>
			<?php
					}
			$extlogins = [];
			$logins = [];
			foreach (t7auth::GetAuthList() as $source)
				$logins[] = 'select \'' . $source . '\' as source, l.id, l.profile, p.name, p.url, ifnull(nullif(p.avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar from login_' . $source . ' as l left join external_profiles as p on p.id=l.profile where l.user=\'' . +$user->ID . '\'';
			if ($logins = $db->query(implode(' union ', $logins)))
				while ($login = $logins->fetch_object()) {
					$extlogins[] = $login;
			?>
				<label>
					<span class=field><input name=avatar value=profile<?= $login->profile; ?> type=radio><img src="<?= htmlspecialchars($login->avatar); ?>" class=avatar>link to <?= $login->source; ?> account <?= htmlspecialchars($login->name); ?></span>
				</label>
			<?php
				}
			if ($user->IsKnown()) {  // only known users can upload an avatar
			?>
				<label>
					<span class=field><input name=avatar value=upload type=radio disabled>upload new image<input id=avatarupload name=avatarfile type=file accept=".jpg, .jpeg, .png, image/jpeg, image/jpg, image/png"></span>
				</label>
			<?php
			}
			?>
		</fieldset>
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
			other contact information here and control who can see it. you must
			provide an e-mail address if you want track7 to e-mail you (like
			when someone sends you a message), but you can set it not to display
			to anyone. all of this information is optional.
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
		<?php
		foreach (t7user::GetProfileTypes() as $site) {
		?>
			<label title="your <?= $site; ?> profile url">
				<span class=label><?= $site; ?>:</span>
				<span class=field>
					<input id=<?= $site; ?>>
					<a class="visibility droptrigger" id=vis_<?= $site; ?> data-value=friends title="shown to my track7 friends" href=#visibility></a>
					<span class=droplist>
						<a class=visibility data-value=friends>my track7 friends</a>
						<a class=visibility data-value=all>everyone</a>
					</span>
				</span>
				<span class=validation></span>
			</label>
		<?php
		}
		?>
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
			another way to keep up-to-date with track7 is to follow
			<a href="https://twitter.com/track7feed">@track7feed</a> on twitter.
			this includes the information on the track7 front page; there is no
			feed for your messages so you will need to enable e-mail
			notifications or visit the site to know when someone has sent you a
			message.
		</p>
	</form>

	<form class=tabcontent id=linkedaccounts>
		<?php
		if ($transition = $db->query('select login from transition_login where id=\'' . +$user->ID . '\' limit 1'))
			$transition = $transition->fetch_object();
		if ($transition) {
		?>
			<h2>username + password</h2>
			<div class=transitionlogin>
				<div class="linkedaccount transition">
					<img class=accounttype src="via/track7.png" alt=track7>
					<div class=actions>
						<?php
						if (count($extlogins)) {
						?>
							<a class=del href=#removetransition title="remove this login option and delete password information from track7"></a>
						<?php
						}
						?>
					</div>
				</div>
				<p class=securitynote>
					track7 passwords are less secure because track7 isn’t encrypted.
					<?php
					if (count($extlogins)) {
					?>
						once you have verified your ability to sign in with one of the
						other accounts listed here, you should remove the username +
						password login option.
					<?php
					} else {
					?>
						you should link an account for login and remove your track7
						username + password, especially if you use the same password
						elsewhere.
					<?php
					}
					?>
				</p>
			</div>
		<?php
		}
		?>
		<h2>linked accounts</h2>
		<div class="linkedaccounts">
			<?php
			foreach ($extlogins as $login) {
			?>
				<div class="linkedaccount <?= $login->source; ?>">
					<?php
					if ($login->url) {
					?>
						<a href="<?= htmlspecialchars($login->url); ?>" title="view the <?= $login->name; ?> profile on <?= $login->source; ?>"><img src="<?= $login->avatar; ?>"></a>
					<?php
					} else {
					?>
						<img src="<?= $login->avatar; ?>" title="<?= $login->source; ?> profiles don’t have pages">
					<?php
					}
					?>
					<div class=actions>
						<?php
						if (count($extlogins) > 1) {
						?>
							<a class=unlink href="#removeaccount" data-source=<?= $login->source; ?> data-id=<?= $login->id; ?> title="unlink this account so it can no longer be used to sign in to track7"></a>
						<?php
						}
						?>
					</div>
				</div>
			<?php
			}
			$logins = count($extlogins);
			if ($transition)
				$logins++;
			?>
		</div>

		<h2>authorize another account</h2>
		<p>
			you currently have <?php echo $logins . ' way';
													if ($logins != 1) echo 's'; ?>
			to sign in to track7, but you can always add another. choose an
			account provider below to sign in and add it as a track7 sign in.
		</p>
		<div id=authchoices>
			<?php
			$auths = t7auth::GetAuthLinks($_SERVER['PHP_SELF'] . '#linkedaccounts', true);
			foreach ($auths as $name => $authurl) {
			?>
				<a href="<?= htmlspecialchars($authurl); ?>" class=<?= $name; ?> title="link your <?= $name; ?> account for sign in"></a>
			<?php
			}
			?>
		</div>
	</form>
</div>
<?php
$html->Close();
