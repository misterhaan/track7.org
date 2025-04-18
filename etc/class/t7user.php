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
	private $Friend = false;  // whether this user considers the logged-in user a friend (additional profile information may be available)
	public $Fan = false;  // whether the logged-in user considers this user a friend
	private $olduid = false;  // uid from the old user system
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
	 * @param integer $id user to look up, if not the logged-in user
	 */
	public function __construct($id = false) {
		if ($id) {  // when not the currently logged-in user
			if (is_numeric($id))  // numeric id, need info for post
				$this->GetBasic($id);
			else
				$this->GetProfileInfo($id);
			return;
		}
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
	 * attempt to log in with the method specified.  redirects to $continue if
	 * successful.
	 * @param string $type who authenticated the user (transition, google)
	 * @param string|t7authRegisterable $id user id, or external authorization object
	 * @param boolean $remember whether an autologin should be set up
	 * @param string $continue local url to redirect to after login is completed
	 */
	public function Login($type, $id, $remember = false, $continue = false) {
		global $db;
		$uid = false;
		if ($type == 'transition' || $type == 'register')
			$uid = $id;
		elseif (t7auth::IsKnown($type)) {
			if ($login = $db->query('select l.user, l.profile, p.useavatar from login_' . $type . ' as l left join external_profiles as p on p.id=l.profile where l.' . t7auth::GetField($type) . '=\'' . $db->escape_string($id->ID) . '\' limit 1'))
				if ($login = $login->fetch_object()) {
					$uid = $login->user;
					$remember = $id->Remember;
					$continue = $id->Continue;
					if ($id->GetUserInfo()) {
						$db->real_query('update external_profiles set name=\'' . $db->escape_string($id->DisplayName) . '\', url=\'' . $db->escape_string($id->ProfileFull) . '\', avatar=\'' . $db->escape_string($id->Avatar) . '\' where id=' . +$login->profile);
						if (+$login->useavatar && $id->Avatar)
							$db->real_query('update users set avatar=\'' . $db->escape_string($id->Avatar) . '\' where id=' . +$uid);
					}
				}
		}
		if ($uid) {
			$_SESSION['user'] = $uid;
			$_SESSION['loginsource'] = $type;
			self::UpdateLastLogin($uid);
			if ($type != 'register') {
				if ($remember)
					$this->CreateRememberToken($uid, $this->StartRememberSeries());
				if (!$continue)
					$continue = '/';
				header('Location: ' . t7format::FullUrl($continue));
				die;
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
	 * whether the user is trusted.
	 * @return boolean
	 */
	public function IsTrusted() {
		return $this->level >= self::LEVEL_TRUSTED;
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
			if ($login = $db->query('select id from transition_login where id=\'' . +$this->ID . '\''))
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
			$logins = [];
			foreach (t7auth::GetAuthList() as $source)
				$logins[] = '(select count(1) from login_' . $source . ' where user=\'' . +$this->ID . '\')';
			if ($logins = $db->query('select ' . implode(' + ', $logins) . ' as num'))
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
	 * get the name for the logged-in user's access level.
	 * @return string name for the logged-in user's access level
	 */
	public function GetLevelName() {
		if ($this->IsAdmin())
			return 'admin';
		if ($this->IsTrusted())
			return 'trusted';
		if ($this->IsKnown())
			return 'known';
		if ($this->IsLoggedIn())
			return 'new';
		return 'anonymous';
	}

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
			case 'google':
				if (substr($url, 0, 25) == 'https://plus.google.com/+')
					return substr($url, 24);
				if (substr($url, 0, 28) == 'https://profiles.google.com/')
					return substr($url, 28);
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
	 * form the unique portion of a profile url back into the full url for a
	 * given site.
	 * @param string $url unique portion of a profile url
	 * @param string $source one of the sites with known profile url formats
	 * @param string $html true if the url is going to be used directly in html
	 * @return string full profile url
	 */
	public static function ExpandProfileLink($url, $source, $html = false) {
		switch ($source) {
			case 'deviantart':
				$url = 'https://' . $url . '.deviantart.com/';
				break;
			case 'facebook':
				$url = 'https://www.facebook.com/' . $url;
				break;
			case 'github':
				$url = 'https://github.com/' . $url;
				break;
			case 'google':
				if ($url[0] == '+')
					$url = 'https://plus.google.com/' . $url;
				else
					$url = 'https://profiles.google.com/' . $url;
				break;
			case 'steam':
				if (preg_match('/^[0-9]+$/', $url))
					$url = 'https://steamcommunity.com/profiles/' . $url;
				else
					$url = 'https://steamcommunity.com/id/' . $url;
				break;
			case 'twitter':
				$url = 'https://twitter.com/' . $url;
				break;
		}
		if ($html)
			$url = htmlspecialchars($url);
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
	 * Look up the user's basic information along with profile information.
	 * @param string $username Username to look up
	 * @return boolean true if successful
	 */
	private function GetProfileInfo($username) {
		global $db, $user;
		if ($u = $db->query('select u.id, u.level, u.username, u.displayname, u.avatar, fr.friend, fa.fan from users as u left join friend as fr on fr.fan=u.id and fr.friend=\'' . +$user->ID . '\' left join friend as fa on fa.friend=u.id and fa.fan=\'' . +$user->ID . '\' where u.username=\'' . $db->escape_string($username) . '\' limit 1'))
			if ($u = $u->fetch_object()) {
				$this->ID = $u->id;
				$this->level = $u->level;
				$this->Username = $u->username;
				$this->DisplayName = $u->displayname ? $u->displayname : $u->username;
				$this->Avatar = $u->avatar ? $u->avatar : self::DEFAULT_AVATAR;
				$this->Friend = $u->friend;
				$this->Fan = $u->fan;
				return true;
			}
		return false;
	}

	/**
	 * clear any tokens for the current autologin series.  should only be used
	 * when user chooses to log out.
	 * @param string $series random number assigned when logging in with remember me checked
	 */
	private function ClearRememberSeries($series) {
		global $db;
		$db->real_query('delete from login_remembered where series=\'' . $db->real_escape_string($series) . '\' or expires>\'' . +time() . '\'');
	}

	/**
	 * generate a new series number.  should only be used when logging in with
	 * remember me checked.  series number is guaranteed to be unique.
	 * @return string new series number, or false if unable to generate one
	 */
	private function StartRememberSeries() {
		global $db;
		do {
			$series = base64_encode(openssl_random_pseudo_bytes(12));
			if ($chk = $db->query('select 1 from login_remembered where series=\'' . $db->real_escape_string($series) . '\' limit 1'))
				$chk = $chk->fetch_object();
		} while ($chk);
		return $series;
	}

	/**
	 * create a new automatic login token and save it to the database and / or a
	 * cookie.
	 * @param integer $id user id to remember
	 * @param string $series random number assigned when logging in with remember me checked
	 * @param boolean $saveToDB whether the new token should be saved to the database
	 * @param boolean $sendCookie whether the new token should be saved in a cookie
	 */
	private function CreateRememberToken($id, $series, $saveToDB = true, $sendCookie = true) {
		$token = openssl_random_pseudo_bytes(32);
		if ($saveToDB) {
			global $db;
			$db->real_query('replace into login_remembered (series, tokenhash, expires, user) values (\'' . $db->real_escape_string($series) . '\', \'' . $db->real_escape_string(base64_encode(hash('sha512', $token, true))) . '\', \'' . (time() + self::COOKIE_LIFE) . '\', \'' . $db->real_escape_string($id) . '\')');
		}
		if ($sendCookie)
			setcookie(self::COOKIE_NAME, $series . ':' . base64_encode($token), time() + self::COOKIE_LIFE, '/');
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
		if ($u = $db->query('select tokenhash, expires, user from login_remembered where series=\'' . $db->real_escape_string($series) . '\' limit 1'))
			if ($u = $u->fetch_object())
				if ($u->expires >= time() && $u->tokenhash == base64_encode(hash('sha512', base64_decode($token), true)))
					return $u->user;
				else {
					// TODO:  token doesn't match, so somebody else stole this login probably!
				}
		return false;
	}
}
