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
	public $SettingsAlerts = 0;  // number of alerts for the user's settings

	/**
	 * Checks a name for uniqueness, length, and allowed characters.
	 * @param string $name Name to check
	 * @param integer $uid ID of the user this name is for, in case the name is in use by this user (optional)
	 * @return string|boolean True if name is allowed; otherwise error message
	 */
	public static function CheckUsername($name, $uid = 0) {
		if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name))
			return 'username can only contain alphanumeric, dash, and underscore characters.';
		return self::CheckName($name, $uid);
	}

	/**
	 * Checks a name for uniqueness and length.
	 * @param string $name Name to check
	 * @param integer $uid ID of the user this name is for, in case the name is in use by this user (optional)
	 * @return string|boolean True if name is allowed; otherwise error message
	 */
	public static function CheckName($name, $uid = 0) {
		global $db;
		if (mb_strlen($name) < 4)
			return 'names must be at least four characters long.';
		if (strlen($name) > 32)
			return 'names must be no longer than 32 bytes (most characters are one byte).';
		if ($dup = $db->query('select 1 from users where (username=\'' . $db->escape_string($name) . '\' or displayname=\'' . $db->escape_string($name) . '\') and not id=\'' . +$uid . '\''))
			if ($dup->num_rows)
				return 'name already in use.';
			else
				return true;
		else
			return 'error checking whether name is already in use.';
	}

	/**
	 * Checks an email address for a reasonable format.
	 * @param string $email email address to check
	 * @return number 1 if email address looks reasonable, 0 if not
	 */
	public static function CheckEmail($email) {
		return preg_match('/^[^@\s]+@[^\.@\s]+\.[^@\s]*[^\.@\s]$/', $email);
	}

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
	 * whether the user is probably not a spambot.
	 * @return boolean
	 */
	public function IsKnown() {
		return $this->level >= self::LEVEL_KNOWN;
	}

	/**
	 * whether the user is the admin.
	 * @return boolean
	 */
	public function IsAdmin() {
		return $this->level >= self::LEVEL_ADMIN;
	}

	/**
	 * whether the user has an old track7 password.
	 * @return bool true if user has a transition login.  0 for database error.
	 */
	public function HasTransitionLogin() {
		if ($this->hasTransitionLogin === 0) {
			global $db;
			if ($login = $db->query('select id from user where passwordhash is not null and id=\'' . +$this->ID . '\''))
				$this->hasTransitionLogin =  $login->num_rows > 0;
		}
		return $this->hasTransitionLogin;
	}
	/**
	 * @var bool cached value of HasTransitionLogin()
	 */
	private $hasTransitionLogin = 0;

	/**
	 * whether the user has a secure (external) login.
	 * @return int number of secure logins this user has, or false on database error
	 */
	public function SecureLoginCount() {
		if ($this->secureLoginCount === false) {
			global $db;
			$logins = 'select count(1) as num from login where user=\'' . +$this->ID . '\'';
			if ($logins = $db->query($logins))
				if ($logins = $logins->fetch_object())
					$this->secureLoginCount = $logins->num;
		}
		return $this->secureLoginCount;
	}
	/**
	 * @var int cached value of SecureLoginCount()
	 */
	private $secureLoginCount = false;

	/**
	 * list of external profile types supported by CollapseProfileLink() and ExpandProfileLink().
	 * @return string[] array of source names for external profiles.
	 */
	public static function GetProfileTypes() {
		// order determines which order they will display on user profiles
		return ['twitter', 'facebook', 'github', 'deviantart', 'steam'];
	}

	/**
	 * strip down a profile url to just the unique portion for a given site.
	 * @param string $url full url to a profile on another site
	 * @param string $source one of the sites with known profile url formats
	 * @return string unique portion of the profile url, or the full url
	 */
	public static function CollapseProfileLink($url, $source) {
		switch ($source) {
			case 'deviantart':
				if (preg_match('/^https?:\/\/([A-Za-z\-]{3,20})\.deviantart\.com/', $url, $match))
					return $match[1];
			case 'facebook':
				if (preg_match('/^https?:\/\/www\.facebook\.com\/([A-Za-z0-9\.]{5,})(\?.*)?$/', $url, $match))
					return $match[1];
			case 'github':
				if (preg_match('/^https?:\/\/github\.com\/([A-Za-z0-9\-]{1,39})\/?$/', $url, $match))
					return $match[1];
			case 'steam':
				if (substr($url, 0, 36) == 'https://steamcommunity.com/profiles/')
					return substr($url, 36);
				if (substr($url, 0, 30) == 'https://steamcommunity.com/id/')
					return substr($url, 30);
				if (substr($url, 0, 35) == 'http://steamcommunity.com/profiles/')
					return substr($url, 35);
				if (substr($url, 0, 29) == 'http://steamcommunity.com/id/')
					return substr($url, 29);
			case 'twitter':
				if (preg_match('/^(https?:\/\/twitter\.com\/|@)([A-Za-z0-9_]{1,15})$/', $url, $match))
					return $match[2];
		}
		return $url;
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
		if ($s = $db->query('select timebase, timeoffset, unreadmsgs from users_settings where id=\'' . $db->real_escape_string($this->ID) . '\' limit 1'))
			if ($s = $s->fetch_object()) {
				$this->DST = $s->timebase != 'gmt';
				$this->tzOffset = $s->timeoffset;
				$this->UnreadMsgs = $s->unreadmsgs;
				$this->SettingsAlerts = +$this->HasTransitionLogin();
				$this->NotifyCount = $this->UnreadMsgs + $this->SettingsAlerts;  // add other types of notifications
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
