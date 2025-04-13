<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for users api requests.
 * @author misterhaan
 */
class usersApi extends t7api {
	/**
	 * write out the documentation for the users api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
		<h2 id=getinfo>get info</h2>
		<p>retrieves basic information on a user by username.</p>

		<h2 id=postregister>post register</h2>
		<p>
			register a new user. the session must have data from an external login
			ready to put into a new track7 account.
		</p>
		<dl class=parameters>
			<dt>csrf</dt>
			<dd>cross-site request forgery token</dd>
			<dt>username</dt>
			<dd>username for the new user. must not already be a username.</dd>
			<dt>displayname</dt>
			<dd>
				display name for the new user. must not already be a display name or
				username. optional; default is to use the username.
			</dd>
			<dt>webside</dt>
			<dd>url to the new user’s website. optional; default none.</dd>
			<dt>useavatar</dt>
			<dd>if true, use the avatar from the login provider.</dd>
			<dt>email</dt>
			<dd>new user’s e-mail address. optional; default none.</dd>
			<dt>linkprofile</dt>
			<dd>
				if true, include the profile url from the login provider in the track7
				profile.
			</dd>
		</dl>

		<h2 id=getsuggest>get suggest</h2>
		<p>retrieves a simple list of users that match the supplied search text.</p>

<?php
	}

	/**
	 * get users information.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function infoAction($ajax) {
		global $db, $user;
		if (isset($_GET['username']))
			if ($u = $db->query('select id, coalesce(nullif(avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar, displayname, username from users where username=\'' . $db->escape_string(trim($_GET['username'])) . '\' and id!=\'' . +$user->ID . '\' limit 1'))
				if ($u = $u->fetch_object())
					$ajax->Data->user = $u;
				else
					$ajax->Fail('user not found.');
			else
				$ajax->Fail('error looking up user', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('required field missing.');
	}

	/**
	 * register a new track7 user after login from an external login provider.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function registerAction($ajax) {
		global $db, $user;
		if (isset($_POST['csrf']))
			if (t7auth::CheckCSRF($_POST['csrf']))
				if (isset($_SESSION['registering']) && t7auth::IsKnown($_SESSION['registering']) && isset($_SESSION[$_SESSION['registering']]))
					if (isset($_POST['username'])) {
						$msg = t7user::CheckUsername($_POST['username'] = trim($_POST['username']));
						if ($msg === true) {
							if (!isset($_POST['displayname']) || true !== t7user::CheckName($_POST['displayname'] = trim($_POST['displayname'])))
								$_POST['displayname'] = '';
							if (isset($_SESSION[$_SESSION['registering']]['avatar'])) {
								// make sure the avatar url points to a readable image
								$c = curl_init($_SESSION[$_SESSION['registering']]['avatar']);
								curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
								curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
								$avatar = curl_exec($c);
								curl_close($c);
								if ($avatar = imagecreatefromstring($avatar))
									imagedestroy($avatar);
								else
									$_SESSION[$_SESSION['registering']]['avatar'] = '';
							} else
								$_SESSION[$_SESSION['registering']]['avatar'] = '';
							if (isset($_POST['website']) && ($_POST['website'] = trim($_POST['website'])) != '' && !t7format::CheckUrl($_POST['website']))
								$_POST['website'] = '';
							$avatar = '';
							if ($_POST['useavatar'] && $_SESSION[$_SESSION['registering']]['avatar'])
								$avatar = $_SESSION[$_SESSION['registering']]['avatar'];
							elseif (isset($_POST['email']) && t7user::CheckEmail(trim($_POST['email']))) {
								$avatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($_POST['email']))) . '?s=128&d=robohash';
								$_POST['useavatar'] = false;
							}
							$db->autocommit(false);  // users row should only actually be created if login row is too
							if ($db->real_query('insert into users (username, displayname, avatar) values (\'' . $db->escape_string($_POST['username']) . '\', \'' . $db->escape_string($_POST['displayname']) . '\', \'' . $db->escape_string($avatar) . '\')')) {
								$uid = $db->insert_id;
								$db->real_query('insert into user select * from users where id=\'' . +$uid . '\'');
								if ($db->real_query('insert into external_profiles (name, url, avatar, useavatar) values (\'' . $db->escape_string($_SESSION[$_SESSION['registering']]['name']) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']]['profile']) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']]['avatar']) . '\', ' . ($_POST['useavatar'] ? 1 : 0) . ')')) {
									$pid = $db->insert_id;
									if ($db->real_query('insert into `login_' . $db->escape_string($_SESSION['registering']) . '` (user, ' . t7auth::GetField($_SESSION['registering']) . ', profile) values (\'' . $db->escape_string($uid) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']][t7auth::GetField($_SESSION['registering'])]) . '\', \'' . $db->escape_string($pid) . '\')')) {
										$db->commit();
										$db->autocommit(true);
										if (isset($_POST['email']) && t7user::CheckEmail($_POST['email'] = trim($_POST['email'])))
											$db->real_query('insert into contact (user, type, contact, visibility) values (\'' . $db->escape_string($uid) . '\', \'email\', \'' . $db->escape_string($_POST['email']) . '\', \'none\')');
										if (isset($_POST['website']))
											$db->real_query('insert into contact (user, type, contact, visibility) values (\'' . $db->escape_string($uid) . '\', \'website\', \'' . $db->escape_string($_POST['website']) . '\', \'all\')');
										if (isset($_POST['linkprofile']))
											$db->real_query('insert into contact (user, type, contact) values (\'' . $db->escape_string($uid) . '\', \'' . $db->escape_string($_SESSION['registering']) . '\', \'' . $db->escape_string(t7user::CollapseProfileLink($_SESSION[$_SESSION['registering']]['profile'], $_SESSION['registering'])) . '\')');
										$user->Login('register', $uid, $_SESSION[$_SESSION['registering']]['remember']);
										$ajax->Data->continue = $_SESSION[$_SESSION['registering']]['continue'];
										unset($_SESSION[$_SESSION['registering']]);
										unset($_SESSION['registering']);
									} else
										$ajax->Fail('database error linking sign in account', $db->errno . ' ' . $db->error);
								} else
									$ajax->Fail('database error caching profile information', $db->errno . ' ' . $db->error);
							} else
								$ajax->Fail('database error registering user', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail($msg);
					} else
						$ajax->Fail('username is required.');
				else
					$ajax->Fail('could not find sign in account information.');
			else
				$ajax->Fail('there was a problem with the verification data.  this can happen if you wait too long on the registration form, so if that could be what happened just try again.');
		else
			$ajax->Fail('verification data missing.');
	}

	/**
	 * suggest matching users.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function suggestAction($ajax) {
		global $db, $user;
		if (isset($_GET['match']) && strlen($_GET['match']) >= 3) {
			$matchsql = $db->escape_string(trim($_GET['match']));
			$matchlike = $db->escape_string(str_replace(['_', '%'], ['\\_', '\\%'], trim($_GET['match'])));
			// some columns aren't needed except to make the order by use unique columns
			if ($us = $db->query('select u.id, coalesce(nullif(u.avatar, \'\'), \'' . t7user::DEFAULT_AVATAR . '\') as avatar, u.displayname, u.username, f.fan as isfriend, u.username=\'' . $matchsql . '\' or u.displayname=\'' . $matchsql . '\' as exact, u.username like \'' . $matchlike . '%\' or u.displayname like \'' . $matchlike . '%\' as start from users as u left join friend as f on f.fan=\'' . +$user->ID . '\' and f.friend=u.id where u.id!=\'' . +$user->ID . '\' and (u.username like \'%' . $matchlike . '%\' or u.displayname like \'%' . $matchlike . '%\') order by isfriend desc, exact desc, start desc, coalesce(nullif(u.displayname, \'\'), u.username) limit 8')) {
				$ajax->Data->users = [];
				while ($u = $us->fetch_object()) {
					// remove ordering columns
					unset($u->exact, $u->start);
					$ajax->Data->users[] = $u;
				}
			} else
				$ajax->Fail('error looking for user suggestions', $db->errno . ' ' . $db->error);
		} else
			$ajax->Fail('at least 3 characters are required to suggest users.');
	}
}
usersApi::Respond();
