<?php
require_once 'environment.php';

/**
 * Access level for a user.
 */
class UserLevel {
	public const Anonymous = 0;
	public const New = 1;
	public const Known = 2;
	public const Trusted = 3;
	public const Admin = 4;

	public static function Name(int $level) {
		return match ($level) {
			self::New => 'new',
			self::Known => 'known',
			self::Trusted => 'trusted',
			self::Admin => 'admin',
			default => 'anonymous'
		};
	}
}

/**
 * A track7 user
 */
class User {
	public const DefaultName = 'random internet person';
	protected const DefaultAvatar = '/images/user.jpg';

	/**
	 * ID of the user, or zero for anonymous user
	 */
	public int $ID = 0;
	/**
	 * Access level this user has
	 */
	public int $Level = UserLevel::Anonymous;
	/**
	 * Username, or empty string for anonymous user
	 */
	public string $Username = '';
	/**
	 * Display name for this user, or their username if they haven't defined a display name
	 */
	public string $DisplayName = self::DefaultName;
	/**
	 * URL to the user's avatar image
	 */
	public string $Avatar = self::DefaultAvatar;

	/**
	 * Create a basic track7 user looking up by ID.
	 * @param mysqli $db Database connection
	 * @param int $id User ID to look up
	 */
	public function __construct(mysqli $db, int $id) {
		try {
			$select = $db->prepare('select id, level, username, displayname, avatar from user where id=?');
			$select->bind_param('i', $id);
			$select->execute();
			$select->bind_result($this->ID, $this->Level, $this->Username, $this->DisplayName, $this->Avatar);
			$select->fetch();
			if (!$this->DisplayName)
				$this->DisplayName = $this->Username;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up basic user information', $mse);
		}
	}
}

/**
 * The track7 user currently logged in
 */
class CurrentUser extends User {
	/**
	 * Whether the user time zone uses daylight saving time
	 */
	public bool $DST = true;
	/**
	 * Hours to offset times to get to the user time zone
	 */
	public int $TzOffset = 0;
	/**
	 * Total number of notifications for the user
	 */
	public int $NotifyCount = 0;
	/**
	 * Total number of unread messages for the user
	 */
	public int $UnreadMsgs = 0;
	/**
	 * Whether the user still has a transitional login
	 */
	public bool $HasTransitionLogin = false;

	protected const SessionKey = 'user';
	protected const CookieName = 'guy';
	protected const CookieLife = 2592000;  // 30 days

	/**
	 * Look up the current user from session or cookie.
	 * @param mysqli $db Database connection
	 */
	public function __construct(?mysqli $db = null) {
		if (!$db)
			return;

		if (isset($_SESSION[self::SessionKey]))
			parent::__construct($db, +$_SESSION[self::SessionKey]);
		elseif (isset($_COOKIE[self::CookieName])) {
			$cookie = explode(':', $_COOKIE[self::CookieName]);
			if (count($cookie) == 2)
				if ($id = $this->GetRememberUserID($db, $cookie[0], $cookie[1])) {
					parent::__construct($db, $id);
					$this->UpdateLastLogin($db, $id);
					// TODO:  create new login cookie token (old code didn't do this)
					$_SESSION[self::SessionKey] = $id;
					$_SESSION['loginsource'] = 'cookie';
				}
		}
		if ($this->IsLoggedIn()) {
			try {
				// TODO:  move to new tables / views
				$select = $db->prepare('select us.timebase != \'gmt\', us.timeoffset, us.unreadmsgs, tl.id is not null from users_settings as us left join transition_login as tl on tl.id=us.id where us.id=? limit 1');
				$select->bind_param('i', $this->ID);
				$select->execute();
				$select->bind_result($this->DST, $this->TzOffset, $this->UnreadMsgs, $this->HasTransitionLogin);
				$select->fetch();
				$this->NotifyCount = $this->UnreadMsgs + $this->HasTransitionLogin;
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error looking up user settings', $mse);
			}
		}
	}

	/**
	 * True if the current user is logged in.
	 */
	public function IsLoggedIn(): bool {
		return $this->Level > UserLevel::Anonymous;
	}

	/**
	 * True if the current user is trusted or an administrator.
	 */
	public function IsTrusted(): bool {
		return $this->Level >= UserLevel::Trusted;
	}

	/**
	 * True if the current user is an administrator.
	 */
	public function IsAdmin(): bool {
		return $this->Level >= UserLevel::Admin;
	}

	/**
	 * update the last time the user logged in.
	 * @param integer $id user id who just logged in
	 */
	private function UpdateLastLogin(mysqli $db, int $id) {
		// TODO:  migrate this table
		$now = time();
		$insert = $db->prepare('update users_stats set lastlogin=? where id=? limit 1');
		$insert->bind_param('ii', $now, $id);
		$insert->execute();
	}

	/**
	 * check the automatic login cookie values against what's remembered in the
	 * database.  can fail if cookie is used past expiration or if cookie has
	 * already been used (neither should happen).
	 * @param $series random number assigned when logging in with remember me checked
	 * @param $token random number assigned at login or when automatic login is used
	 * @return int user id or zero if unable to remember.
	 */
	private function GetRememberUserID(mysqli $db, string $series, string $token): int {
		// TODO:  migrate this table
		$select = $db->prepare('select tokenhash, expires, user from login_remembered where series=? limit 1');
		$select->bind_param('s', $series);
		$select->execute();
		$select->bind_result($tokenhash, $expires, $id);
		if ($select->fetch() && $expires >= time())
			if ($tokenhash == base64_encode(hash('sha512', base64_decode($token), true)))
				return $id;
			else {
				// TODO:  delete all remembered logins for this user due to token mismatch
			}
		return 0;
	}
	// TODO:  delete expired login_remembered rows at some point
}
