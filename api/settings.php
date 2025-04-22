<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for settings api requests.
 * @author misterhaan
 */
class usersApi extends t7api {
	const MAX_AVATAR_SIZE = 128;

	/**
	 * write out the documentation for the settings api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
		<h2 id=getcheckDeviantart>get checkDeviantart</h2>
		<p>checks a deviantart profile setting.</p>

		<h2 id=getcheckEmail>get checkEmail</h2>
		<p>checks an email address for a profile.</p>

		<h2 id=getcheckFacebook>get checkFacebook</h2>
		<p>checks a facebook profile setting.</p>

		<h2 id=getcheckGithub>get checkGithub</h2>
		<p>checks a github profile setting.</p>

		<h2 id=getcheckGoogle>get checkGoogle</h2>
		<p>checks a google profile setting.</p>

		<h2 id=getcheckName>get checkName</h2>
		<p>checks a display name.</p>

		<h2 id=getcheckSteam>get checkSteam</h2>
		<p>checks a steam profile setting.</p>

		<h2 id=getcheckTwitter>get checkTwitter</h2>
		<p>checks a twitter profile setting.</p>

		<h2 id=getcheckUrl>get checkUrl</h2>
		<p>checks a website profile setting.</p>

		<h2 id=getcheckUsername>get checkUsername</h2>
		<p>checks a username.</p>

		<h2 id=getcontact>get contact</h2>
		<p>retrieves contact settings for the current user.</p>

		<h2 id=postcontact>post contact</h2>
		<p>saves the contact settings for the current user.</p>

		<h2 id=getnotification>get notification</h2>
		<p>retrieves notification settings for the current user.</p>

		<h2 id=postnotification>post notification</h2>
		<p>saves the notification settings for the current user.</p>

		<h2 id=getprofile>get profile</h2>
		<p>retrieves profile settings for the current user.</p>

		<h2 id=postprofile>post profile</h2>
		<p>saves the profile settings for the current user.</p>

		<h2 id=postremoveLoginAccount>post removelLoginAccount</h2>
		<p>removes a login account from the current user.</p>

		<h2 id=postremoveTransitionalLogin>post removeTransitionalLogin</h2>
		<p>removes the transitional login from the current user.</p>

		<h2 id=gettime>get time</h2>
		<p>retrieves time settings for the current user.</p>

		<h2 id=posttime>post time</h2>
		<p>saves the time settings for the current user.</p>

<?php
	}

	/**
	 * check deviantart profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkDeviantartAction($ajax) {
		if (isset($_GET['deviantart'])) {
			$deviantart = trim($_GET['deviantart']);
			if (self::CheckDeviantart($deviantart)) {
				if ($deviantart != $_GET['deviantart'])
					$ajax->Data->replace = $deviantart;
			} else
				$ajax->Fail('invalid deviantart profile.  please enter your deviantart username or the url to your deviantart profile.');
		} else
			$ajax->Fail('deviantart missing.');
	}

	/**
	 * check email profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkEmailAction($ajax) {
		if (isset($_GET['email'])) {
			if (strtolower(substr(trim($_GET['email']), -12)) == '@example.com')
				$ajax->Fail('e-mail address is not required.  please don’t enter a fake one.');
			else if (!t7user::CheckEmail(trim($_GET['email'])))
				$ajax->Fail('doesn’t look like an e-mail address.');
		} else
			$ajax->Fail('email missing.');
	}

	/**
	 * check facebook profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkFacebookAction($ajax) {
		if (isset($_GET['facebook'])) {
			$facebook = trim($_GET['facebook']);
			if (self::CheckFacebook($facebook)) {
				if ($facebook != $_GET['facebook'])
					$ajax->Data->replace = $facebook;
			} else
				$ajax->Fail('invalid facebook profile.  please enter your facebook username or the url to your facebook profile.');
		} else
			$ajax->Fail('facebook missing.');
	}

	/**
	 * check github profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkGithubAction($ajax) {
		if (isset($_GET['github'])) {
			$github = trim($_GET['github']);
			if (self::CheckGithub($github)) {
				if ($github != $_GET['github'])
					$ajax->Data->replace = $github;
			} else
				$ajax->Fail('invalid github profile.  please enter your github username or the url to your github profile.');
		} else
			$ajax->Fail('github missing.');
	}

	/**
	 * check google profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkGoogleAction($ajax) {
		if (isset($_GET['google'])) {
			$google = trim($_GET['google']);
			if (self::CheckGoogle($google)) {
				if ($google != $_GET['google'])
					$ajax->Data->replace = $google;
			} else
				$ajax->Fail('invalid google profile.  please enter your google plus name (with the +) or the url to your google plus profile.');
		} else
			$ajax->Fail('google missing.');
	}

	/**
	 * check display name profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkNameAction($ajax) {
		global $user;
		if (isset($_GET['name'])) {
			$msg = t7user::CheckName(trim($_GET['name']), +$user->ID);
			if ($msg !== true)
				$ajax->Fail($msg);
		} else
			$ajax->Fail('name missing.');
	}

	/**
	 * check steam profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkSteamAction($ajax) {
		if (isset($_GET['steam'])) {
			$steam = trim($_GET['steam']);
			if (self::CheckSteam($steam)) {
				if ($steam != $_GET['steam'])
					$ajax->Data->replace = $steam;
			} else
				$ajax->Fail('invalid steam profile.  please enter your steam custom url or the url to your steam community profile.');
		} else
			$ajax->Fail('steam missing.');
	}

	/**
	 * check twitter profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkTwitterAction($ajax) {
		if (isset($_GET['twitter'])) {
			$twitter = trim($_GET['twitter']);
			if (self::CheckTwitter($twitter)) {
				if ($twitter != $_GET['twitter'])
					$ajax->Data->replace = $twitter;
			} else
				$ajax->Fail('invalid twitter username.  please enter your twitter username or the url to your twitter profile.');
		} else
			$ajax->Fail('twitter missing.');
	}

	/**
	 * check website profile setting.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkUrlAction($ajax) {
		if (isset($_GET['url'])) {
			$url = trim($_GET['url']);
			if (t7format::CheckUrl($url)) {
				if ($url != $_GET['url'])
					$ajax->Data->replace = $url;
			} else
				$ajax->Fail('invalid url.  please enter a url to a reachable web page.');
		} else
			$ajax->Fail('url missing.');
	}

	/**
	 * check username.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkUsernameAction($ajax) {
		global $user;
		if (isset($_GET['username'])) {
			$msg = t7user::CheckUsername(trim($_GET['username']), +$user->ID);
			if ($msg !== true)
				$ajax->Fail($msg);
		} else
			$ajax->Fail('username missing.');
	}

	/**
	 * load or save contact settings for the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function contactAction($ajax) {
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				self::LoadContact($ajax);
				break;
			case 'POST':
				self::SaveContact($ajax);
				break;
		}
	}

	/**
	 * load or save notification settings for the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function notificationAction($ajax) {
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				self::LoadNotification($ajax);
				break;
			case 'POST':
				self::SaveNotification($ajax);
				break;
		}
	}

	/**
	 * load or save profile settings for the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function profileAction($ajax) {
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				self::LoadProfile($ajax);
				break;
			case 'POST':
				self::SaveProfile($ajax);
				break;
		}
	}

	/**
	 * remove a login account from the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function removeLoginAccountAction($ajax) {
		global $db, $user;
		$ajax->Fail(print_r($_POST, true));
		if ($user->IsLoggedIn())
			if ($user->SecureLoginCount() > 1)
				if (isset($_POST['source']) && isset($_POST['id']) && t7auth::IsKnown($_POST['source']))
					if ($db->real_query('delete from login where site=\'' . $_POST['source'] . '\' and id=\'' . $db->escape_string($_POST['id']) . '\' and user=\'' . +$user->ID . '\'')); // success
					else
						$ajax->Fail('unable to remove sign-in account because something went wrong with the database.');
				else
					$ajax->Fail('invalid or missing account type and id.');
			else
				$ajax->Fail('unable to remove sign-in account because it is your only secure way of signing in.  link another account for sign in and try again.');
		else
			$ajax->Fail('unable to remove sign-in account because you are not signed in.  most likely your session has expired because it’s been too long since you’ve done anything, in which case you need to sign in again.');
	}

	/**
	 * remove transitional login from the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function removeTransitionalLoginAction($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if ($user->SecureLoginCount()) {
				if (!$db->real_query('update user set passwordhash=null where id=\'' . +$user->ID . '\''))
					$ajax->Fail('unable to remove password because something went wrong with the database.');
			} else
				$ajax->Fail('unable to remove password because it is your only way of signing in.  link another account for sign in and try again.');
		else
			$ajax->Fail('unable to remove password because you are not signed in.  most likely your session has expired because it’s been too long since you’ve done anything, in which case you need to sign in again.');
	}

	/**
	 * load or save time settings for the current user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function timeAction($ajax) {
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				self::LoadTime($ajax);
				break;
			case 'POST':
				self::SaveTime($ajax);
				break;
		}
	}

	private static function LoadContact($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn()) {
			$ajax->Data = (object)['email' => '', 'vis_email' => 'none', 'website' => '', 'vis_website' => 'all'];
			foreach (t7user::GetProfileTypes() as $source) {
				$vis = 'vis_' . $source;
				$ajax->Data->$source = '';
				$ajax->Data->$vis = 'friends';
			}
			if ($contacts = $db->query('select type, contact, visiblility form contact where user=\'' . +$user->ID . '\'')) {
				while ($contact = $contacts->fetch_object()) {
					$ajax->Data->{$contact->type} = $contact->contact;
					$ajax->Data->{'vis_' . $contact->type} = $contact->visibility;
				}
			} else
				$ajax->Fail('error looking up contact information.');
		} else
			$ajax->Fail('unable to load contact information because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function SaveContact($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if (isset($_POST['email'], $_POST['vis_email'], $_POST['website'], $_POST['vis_website']) && self::AreAllProfileFieldsSet())
				if (strtolower(substr($_POST['email'] = trim($_POST['email']), -12)) != '@example.com')
					if ($_POST['email'] == '' || t7user::CheckEmail($_POST['email']))
						if (self::CheckEmailVisibility($_POST['vis_email']))
							if ('' == ($_POST['website'] = trim($_POST['website'])) || t7format::CheckUrl($_POST['website']))
								if (self::CheckContactVisibilty($_POST['vis_website'])) {
									if (self::AreAllProfileFieldsValid()) {
										if ($_POST['email']) {
											if (!$db->real_query('replace into contact (user, type, contact, visibility) values (\'' . +$user->ID . '\', \'email\', \'' . $db->escape_string($_POST['email']) . '\', \'' . $db->escape_string($_POST['vis_email']) . '\')'))
												$ajax->Fail('error saving email address', $db->errno . ' ' . $db->error);
										} elseif (!$db->real_query('delete from contact where user=\'' . +$user->ID . '\' and type=\'email\''))
											$ajax->Fail('error clearing email address', $db->errno . ' ' . $db->error);
										if ($_POST['website']) {
											if (!$db->real_query('replace into contact (user, type, contact, visibility) values (\'' . +$user->ID . '\', \'website\', \'' . $db->escape_string($_POST['website']) . '\', \'' . $db->escape_string($_POST['vis_website']) . '\')'))
												$ajax->Fail('error saving website', $db->errno . ' ' . $db->error);
										} elseif (!$db->real_query('delete from contact where user=\'' . +$user->ID . '\' and type=\'website\''))
											$ajax->Fail('error clearing website', $db->errno . ' ' . $db->error);
										foreach (t7user::GetProfileTypes() as $type)
											if ($_POST[$type]) {
												if (!$db->real_query('replace into contact (user, type, contact, visibility) values (\'' . +$user->ID . '\', \'' . $type . '\', \'' . $db->escape_string($_POST[$type]) . '\', \'' . $db->escape_string($_POST['vis_' . $type]) . '\')'))
													$ajax->Fail('error saving ' . $type . ' profile link', $db->errno . ' ' . $db->error);
											} elseif (!$db->real_query('delete from contact where user=\'' . +$user->ID . '\' and type=\'' . $type . '\''))
												$ajax->Fail('error clearing ' . $type . ' profile link', $db->errno . ' ' . $db->error);
									}
								} else
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
	}

	private static function AreAllProfileFieldsSet() {
		foreach (t7user::GetProfileTypes() as $type)
			if (!isset($_POST[$type], $_POST['vis_' . $type]))
				return false;
		return true;
	}

	private static function AreAllProfileFieldsValid() {
		global $ajax;
		foreach (t7user::GetProfileTypes() as $type)
			if ('' == ($_POST[$type] = trim($_POST[$type])) || self::CheckProfileField($type))
				if (self::CheckContactVisibilty($_POST['vis_' . $type]));  // keep going
				else {
					$ajax->Fail('invalid ' . $type . ' visibility.  trust no one.');
					return false;
				}
			else {
				$ajax->Fail('unable to figure out what you meant for ' . $type . '.  please enter the url to your profile.');
				return false;
			}
	}

	private static function CheckProfileField($type) {
		switch ($type) {
			case 'deviantart':
				return self::CheckDeviantart($_POST[$type]);
			case 'facebook':
				return self::CheckFacebook($_POST[$type]);
			case 'github':
				return self::CheckGithub($_POST[$type]);
			case 'google':
				return self::CheckGoogle($_POST[$type]);
			case 'steam':
				return self::CheckSteam($_POST[$type]);
			case 'twitter':
				return self::CheckTwitter($_POST[$type]);
		}
		return false;
	}

	private static function CheckDeviantart(&$value) {
		$value = t7user::CollapseProfileLink($value, 'deviantart');
		return preg_match('/^[A-Za-z\-]{3,20}$/', $value);
	}

	private static function CheckFacebook(&$value) {
		$value = t7user::CollapseProfileLink($value, 'facebook');
		return preg_match('/^[A-Za-z0-9\.]{5,}$/', $value);
	}

	private static function CheckGithub(&$value) {
		$value = t7user::CollapseProfileLink($value, 'github');
		return preg_match('/^[A-Za-z0-9\-]{1,39}$/', $value);
	}

	private static function CheckGoogle(&$value) {
		$value = t7user::CollapseProfileLink($value, 'google');
		return true;
	}

	private static function CheckSteam(&$value) {
		$value = t7user::CollapseProfileLink($value, 'steam');
		return true;
	}

	private static function CheckTwitter(&$value) {
		$value = t7user::CollapseProfileLink($value, 'twitter');
		return preg_match('/^[A-Za-z0-9_]{1,15}$/', $value);
	}

	private static function CheckEmailVisibility($value) {
		return in_array($value, array('none', 'friends', 'users', 'all'));
	}

	private static function CheckContactVisibilty($value) {
		return in_array($value, array('friends', 'all'));
	}

	private static function LoadNotification($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if ($notif = $db->query('select e.contact as email, coalesce(s.emailnewmsg, 1) as emailnewmsg from users as u left join contact as e on e.user=u.id and e.type=\'email\' left join users_settings as s on s.id=u.id where u.id=\'' . +$user->ID . '\' limit 1'))
				if ($notif = $notif->fetch_object()) {
					$notif->emailnewmsg = $notif->emailnewmsg == true;
					$ajax->Data = $notif;
				} else
					$ajax->Data = (object)['email' => '', 'emailnewmsg' => true];
			else
				$ajax->Fail('error looking up notification settings.');
		else
			$ajax->Fail('unable to load notification settings because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function SaveNotification($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if (isset($_POST['notifymsg']))
				if ($db->query('insert into users_settings (id, emailnewmsg) values (\'' . +$user->ID . '\', \'' . +$_POST['notifymsg'] . '\') on duplicate key update emailnewmsg=\'' . +$_POST['notifymsg'] . '\''));  // nothing to send back
				else
					$ajax->Fail('error saving notification settings.');
			else
				$ajax->Fail('required field not present.');
		else
			$ajax->Fail('unable to save notification settings because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function LoadProfile($ajax) {
		global $user;
		if ($user->IsLoggedIn()) {
			$ajax->Data->username = $user->Username;
			$ajax->Data->displayname = $user->DisplayName == $user->Username ? '' : $user->DisplayName;
		} else
			$ajax->Fail('unable to load profile because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function SaveProfile($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if (isset($_POST['username']))
				if (true === $msg = t7user::CheckUsername($_POST['username'] = trim($_POST['username']), +$user->ID)) {
					$_POST['displayname'] = trim($_POST['displayname']);
					if ($_POST['displayname'] == $_POST['username'] || true !== t7user::CheckName($_POST['displayname'], +$user->ID))
						$_POST['displayname'] = '';
					$ajax->Data->avatar = false;
					self::SaveProfileAvatar($ajax);
					if ($ajax->Data->avatar !== false)
						if ($db->real_query('update users set username=\'' . $db->escape_string($_POST['username']) . '\', displayname=\'' . $db->escape_string($_POST['displayname']) . '\', avatar=\'' . $db->escape_string($ajax->Data->avatar) . '\' where id=\'' . +$user->ID . '\' limit 1')) {
							if (!$ajax->Data->avatar)
								$ajax->Data->avatar = t7user::DEFAULT_AVATAR;  // even thought we store nothing in the database, the page needs an acual image
							$ajax->Data->username = $_POST['username'];
							$ajax->Data->displayname = $_POST['displayname'] ? $_POST['displayname'] : $_POST['username'];
						} else
							$ajax->Fail('error saving profile', $db->errno . ' ' . $db->error);
				} else
					$ajax->Fail($msg);
			else
				$ajax->Fail('username is required.');
		else
			$ajax->Fail('unable to save profile because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function SaveProfileAvatar($ajax) {
		global $db, $user;
		if (!isset($_POST['avatar']) || $_POST['avatar'] == 'current')
			$ajax->Data->avatar = $user->Avatar;
		elseif ($_POST['avatar'] == 'none') {
			$ajax->Data->avatar = '';
			self::UnlinkProfileAvatars();
			self::DeleteUploadedAvatars();
		} elseif ($_POST['avatar'] == 'gravatar') {
			if ($email = $db->query('select contact as email from contact where user=' . +$user->ID . ' and type=\'email\''))
				if ($email = $email->fetch_object())
					if ($email->email) {
						$ajax->Data->avatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email->email))) . '?s=128&d=robohash';
						self::UnlinkProfileAvatars();
						self::DeleteUploadedAvatars();
					} else
						$ajax->Fail('unable to use profile picture from gravatar because you do not have an e-mail address listed.');
				else
					$ajax->Fail('unable to use profile picture from gravatar because you do not have an e-mail address listed.');
			else
				$ajax->Fail('unable to use profile picture from gravatar due to a database error looking up e-mail address.');
		} elseif (substr($_POST['avatar'], 0, 7) == 'profile') {
			if (list($source, $id) = explode('/', substr($_POST['avatar'], 7)))
				if ($avatar = $db->query('select avatar from login where source=\'' . $source . '\' and id=\'' . $id . '\''))
					if ($avatar = $avatar->fetch_object()) {
						$ajax->Data->avatar = $avatar->avatar;
						$db->real_query('update login set useavatar=if(site=\'' . $source . '\' and id=\'' . $id . '\', true, false) where user=\'' . +$user->ID . '\'');
						self::DeleteUploadedAvatars();
					} else
						$ajax->Fail('unable to set profile picture because profile was not found.');
				else
					$ajax->Fail('error looking up external profile to set profile picture.');
			else
				$ajax->Fail('external profile for profile picture not specified or specified incorrectly.');
		} elseif ($_POST['avatar'] == 'upload')
			self::SaveProfileAvatarUploaded($ajax);
		else
			$ajax->Fail('unrecognized profile picture source.');
	}

	private static function SaveProfileAvatarUploaded($ajax) {
		global $db, $user;
		if ($user->IsKnown())
			if (isset($_FILES['avatarfile']) && $_FILES['avatarfile']['size']) {
				if ($ext = t7file::GetImageExtension($_FILES['avatarfile'])) {
					self::DeleteUploadedAvatars();
					$path = '/user/avatar/' . $user->Username . '.' . str_replace('e', '', $ext);
					$ajax->Data->avatar = $path;
					$path = $_SERVER['DOCUMENT_ROOT'] . $path;
					$exif = $ext == 'jpeg' ? @exif_read_data($_FILES['avatarfile']['tmp_name'], 'EXIF', true) : false;
					t7file::SaveUploadedImage($_FILES['avatarfile'], $ext, [$path => self::MAX_AVATAR_SIZE], $exif, true);
					self::UnlinkProfileAvatars();
				} else
					$ajax->Fail('cannot accept profile picture upload:  only jpeg and png images allowed.');
				@unlink($_FILES['avatarfile']['tmp_name']);
			} else
				$ajax->Fail('profile picture upload selected but file was not provided.');
		else
			$ajax->Fail('uploaded profile pictures are only available to known, trusted, or admin users.');
	}

	private static function UnlinkProfileAvatars() {
		global $db, $user;
		$db->real_query('update login set linkavatar=false where user=\'' . +$user->ID . '\' and useavatar=1');
	}

	private static function DeleteUploadedAvatars() {
		global $user;
		$path = $_SERVER['DOCUMENT_ROOT'] . '/user/avatar/' . $user->Username;
		@unlink($path . '.png');
		@unlink($path . '.jpg');
	}

	private static function LoadTime($ajax) {
		global $user;
		if ($user->IsLoggedIn()) {
			$ajax->Data->currenttime = t7format::LocalDate('g:i a', time());
			$ajax->Data->dst = $user->DST;
		} else
			$ajax->Fail('unable to load time settings because you are not signed in.  this can happen if you have left the page open for too long.');
	}

	private static function SaveTime($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn()) {
			if (isset($_POST['currenttime']) && isset($_POST['dst'])) {
				$_POST['dst'] = $_POST['dst'] == 'true';
				$st = time();
				$lt = $_POST['dst'] ? strtotime(trim($_POST['currenttime'])) : strtotime(trim($_POST['currenttime']) . ' GMT');
				$offset = round(($lt - $st) / 900) * 900;
				if ($db->real_query('update users_settings set timebase=\'' . ($_POST['dst'] ? 'server' : 'gmt') . '\', timeoffset=\'' . +$offset . '\' where id=\'' . +$user->ID . '\' limit 1')) {;  // success is default
				} else
					$ajax->Fail('error saving time zone', $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('did not receive new time settings, so nothing to do.');
		} else
			$ajax->Fail('unable to save time settings because you are not signed in.  this can happen if you have left the page open for too long.');
	}
}
usersApi::Respond();
