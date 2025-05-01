<?php
class t7user {
	// level is a simple security system.  higher levels are granted all the
	//   priveleges of lower levels.
	const LEVEL_ANONYMOUS = 0;
	const LEVEL_NEW = 1;
	const LEVEL_KNOWN = 2;
	const LEVEL_TRUSTED = 3;
	const LEVEL_ADMIN = 4;

	const COOKIE_NAME = 'guy';
	const COOKIE_LIFE = 2592000;  // 30 days

	const DEFAULT_AVATAR = '/images/user.jpg';
	const DEFAULT_NAME = 'random internet person';

	public $ID = false;
	public $Username = false;  // username of the current user for profile link
	public $DisplayName = self::DEFAULT_NAME;  // display name of the current user
	public $Avatar = false;  // avatar url for the current user
	public $Fan = false;  // whether the logged-in user considers this user a friend
	private $level = self::LEVEL_ANONYMOUS;  // access level of the current user (see LEVEL_* constants)

	public $DST = true;  // true for server time (which observes daylight saving time)
	public $tzOffset = 0;  // offset (in seconds) from server time (if $DST is true) or gmt
	public $NotifyCount = 0;  // number of notifications to show in the user menu
	public $UnreadMsgs = 0;  // number of conversations this user hasn't read yet

	/**
	 * create a user object either from the specified id or looking for an id
	 * stored in the session or a cookie.
	 */
	public function __construct() {
		if (isset($_SESSION['user'])) {
			if ($this->GetBasic($_SESSION['user'])) {
				$this->GetSettings();
				return;
			}
		}
		if (isset($_COOKIE[self::COOKIE_NAME])) {
			$cookie = explode(':', $_COOKIE[self::COOKIE_NAME]);
			if (count($cookie) == 2)
				if ($id = $this->Remember($cookie[0], $cookie[1])) {
					$_SESSION['user'] = $id;
					$_SESSION['loginsource'] = 'cookie';
					$this->UpdateLastLogin($id);
					if ($this->GetBasic($id))
						$this->GetSettings();
					return;
				}
		}
	}

	/**
	 * whether the user is logged in (vs. anonymous).
	 * @return boolean true if a user is logged in
	 */
	public function IsLoggedIn() {
		return $this->level > self::LEVEL_ANONYMOUS;
	}

	/**
	 * whether the user is the admin.
	 * @return boolean
	 */
	public function IsAdmin() {
		return $this->level >= self::LEVEL_ADMIN;
	}

	/**
	 * update the last time the user logged in.
	 * @param integer $id user id who just logged in
	 */
	private function UpdateLastLogin($id) {
		global $db;
		$db->real_query('update user set lastlogin=now() where id=\'' . +$id . '\' limit 1');
	}

	/**
	 * look up the user's basic information.
	 * @param integer $id user id to look up
	 * @return boolean true if successful
	 */
	private function GetBasic($id) {
		global $db;
		if ($u = $db->query('select level, username, displayname, avatar from users where id=\'' . $db->real_escape_string($id) . '\' limit 1'))
			if ($u = $u->fetch_object()) {
				$this->ID = $id;
				$this->level = $u->level;
				$this->Username = $u->username;
				$this->DisplayName = $u->displayname ? $u->displayname : $u->username;
				$this->Avatar = $u->avatar ? $u->avatar : self::DEFAULT_AVATAR;
				return true;
			}
		return false;
	}

	/**
	 * look up the user's settings.  generally only useful for the logged-in
	 * user.
	 * @return boolean true if successful
	 */
	private function GetSettings() {
		global $db;
		if ($s = $db->query('select timebase, timeoffset from settings where user=\'' . $db->real_escape_string($this->ID) . '\' limit 1'))
			if ($s = $s->fetch_object()) {
				$this->DST = $s->timebase != 'gmt';
				$this->tzOffset = $s->timeoffset;
				$select = $db->prepare('select count(1) from users_messages as m left join users_conversations as c on c.id=m.conversation where (c.thisuser=? or c.thatuser=?) and m.author!=? and m.hasread=false');
				$select->bind_param('iii', $this->ID, $this->ID, $this->ID);
				$select->execute();
				$select->bind_result($this->UnreadMsgs);
				$select->fetch();
				$this->NotifyCount = $this->UnreadMsgs;  // add other types of notifications
				return true;
			}
		return false;
	}

	/**
	 * check the automatic login cookie values against what's remembered in the
	 * database.  can fail if cookie is used past expiration or if cookie has
	 * already been used (neither should happen).
	 * @param string $series random number assigned when logging in with remember me checked
	 * @param string $token random number assigned at login or when automatic login is used
	 * @return mixed user id or false if unable to remember.
	 */
	private function Remember($series, $token) {
		global $db;
		if ($u = $db->query('select tokenhash, unix_timestamp(expires), user from remember where series=\'' . $db->real_escape_string($series) . '\' limit 1'))
			if ($u = $u->fetch_object())
				if ($u->expires >= time() && $u->tokenhash == base64_encode(hash('sha512', base64_decode($token), true)))
					return $u->user;
				else {
					// token doesn't match, so somebody else stole this login probably!
				}
		return false;
	}
}
