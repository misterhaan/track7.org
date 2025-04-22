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
