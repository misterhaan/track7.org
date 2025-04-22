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
	 * @param int|string $id User ID or username to look up
	 */
	public function __construct(mysqli $db, int|string $id) {
		try {
			$select = $db->prepare('select id, level, username, displayname, avatar from user where id=? or username=?');
			$select->bind_param('is', $id, $id);
			$select->execute();
			$select->bind_result($this->ID, $this->Level, $this->Username, $this->DisplayName, $this->Avatar);
			$select->fetch();
			if (!$this->DisplayName)
				$this->DisplayName = $this->Username;
			if (!$this->Avatar)
				$this->Avatar = self::DefaultAvatar;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up basic user information', $mse);
		}
	}

	public static function IdAvailable(mysqli $db, CurrentUser $user, string $newID): ValidationResult {
		if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $newID))
			return new ValidationResult('invalid', 'username can only contain alphanumeric, dash, and underscore characters.');
		return self::NameAvailable($db, $user, $newID);
	}

	public static function NameAvailable(mysqli $db, CurrentUser $user, string $newID): ValidationResult {
		if (mb_strlen($newID) < 4)
			return new ValidationResult('invalid', 'names must be at least four characters long.');
		if (strlen($newID) > 32)
			return new ValidationResult('invalid', 'names must be no longer than 32 bytes (most characters are one byte).');
		try {
			$select = $db->prepare('select 1 from user where (username=? or displayname=?) and id!=? limit 1');
			$select->bind_param('ssi', $newID, $newID, $user->ID);
			$select->execute();
			if ($select->fetch())
				return new ValidationResult('invalid', 'name is already in use.');
			return new ValidationResult('valid');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking if name is available', $mse);
		}
	}
}

class DetailedUser extends User {
	public string $LevelName;
	public TimeTagData $Registered;
	public TimeTagData $LastLogin;
	public bool $Friend;
	public Rank $Posts;
	public Rank $Comments;
	public Rank $Fans;
	public Rank $Friends;
	public Rank $Votes;

	private function __construct(CurrentUser $user, int $id, int $level, string $username, string $displayname, string $avatar, int $registered, int $lastlogin, bool $friend, ?int $posts, ?int $postrank, ?int $comments, ?int $commentrank, ?int $fans, ?int $fanrank, ?int $friends, ?int $friendrank, ?int $votes, ?int $voterank) {
		require_once 'formatDate.php';
		$this->ID = $id;
		$this->Level = $level;
		$this->LevelName = UserLevel::Name($level);
		$this->Username = $username;
		$this->DisplayName = $displayname ? $displayname : $username;
		$this->Avatar = $avatar ? $avatar : self::DefaultAvatar;
		$this->Registered = new TimeTagData($user, 'ago', $registered, FormatDate::Long);
		$this->LastLogin = new TimeTagData($user, 'ago', $lastlogin, FormatDate::Long);
		$this->Friend = $friend;
		$this->Posts = new Rank($posts, $postrank);
		$this->Comments = new Rank($comments, $commentrank);
		$this->Fans = new Rank($fans, $fanrank);
		$this->Friends = new Rank($friends, $friendrank);
		$this->Votes = new Rank($votes, $voterank);
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['login']) || !$_GET['login'])
			return null;
		$username = trim($_GET['login']);
		try {
			$select = $db->prepare('select u.id, u.level, u.username, u.displayname, u.avatar, unix_timestamp(u.registered), unix_timestamp(u.lastlogin), not isnull(f.fan), r.posts, r.postrank, r.comments, r.commentrank, r.fans, r.fanrank, r.friends, r.friendrank, r.votes, r.voterank from user as u left join friend as f on f.friend=u.id and f.fan=? left join ranking as r on r.user=u.id where u.username=? limit 1');
			$select->bind_param('is', $user->ID, $username);
			$select->execute();
			$select->bind_result($id, $level, $username, $displayname, $avatar, $registered, $lastlogin, $friend, $posts, $postrank, $comments, $commentrank, $fans, $fanrank, $friends, $friendrank, $votes, $voterank);
			if ($select->fetch())
				return new self($user, $id, $level, $username, $displayname, $avatar, $registered, $lastlogin, $friend, $posts, $postrank, $comments, $commentrank, $fans, $fanrank, $friends, $friendrank, $votes, $voterank);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up user', $mse);
		}
		return null;
	}

	public static function List(mysqli $db, CurrentUser $user): UserList {
		try {
			$select = $db->prepare('select u.id, u.level, u.username, u.displayname, u.avatar, unix_timestamp(u.registered), unix_timestamp(u.lastlogin), not isnull(f.fan), r.posts, r.postrank, r.comments, r.commentrank, r.fans, r.fanrank, r.friends, r.friendrank, r.votes, r.voterank from user as u left join friend as f on f.friend=u.id and f.fan=? left join ranking as r on r.user=u.id order by u.lastlogin desc');
			$select->bind_param('i', $user->ID);
			$select->execute();
			$select->bind_result($id, $level, $username, $displayname, $avatar, $registered, $lastlogin, $friend, $posts, $postrank, $comments, $commentrank, $fans, $fanrank, $friends, $friendrank, $votes, $voterank);
			$result = new UserList();
			while ($select->fetch())
				$result->Users[] = new self($user, $id, $level, $username, $displayname, $avatar, $registered, $lastlogin, $friend, $posts, $postrank, $comments, $commentrank, $fans, $fanrank, $friends, $friendrank, $votes, $voterank);
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up users', $mse);
		}
	}
}

class Rank {
	public int $Count;
	public int $Rank;

	public function __construct(?int $count, ?int $rank) {
		$this->Count = +$count;
		$this->Rank = $count ? +$rank : 0;
	}
}

class UserList {
	/**
	 * @var DetailedUser[] Group of users loaded
	 */
	public array $Users = [];
	/**
	 * Whether there are more users to load
	 */
	public bool $HasMore = false;
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
	 * Total number of unread messages for the user
	 */
	public int $UnreadMsgs = 0;

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
					$this->UpdateLastLogin($db);
					$this->UpdateRememberSeries($db, $cookie[0]);
					$_SESSION[self::SessionKey] = $id;
					$_SESSION['loginsource'] = 'cookie';
				}
		}
		if ($this->IsLoggedIn()) {
			try {
				// TODO:  move settings to new table / view
				$select = $db->prepare('select timebase!=\'gmt\', timeoffset, unreadmsgs from users_settings where id=? limit 1');
				$select->bind_param('i', $this->ID);
				$select->execute();
				$select->bind_result($this->DST, $this->TzOffset, $this->UnreadMsgs);
				$select->fetch();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error looking up user settings', $mse);
			}
		}
	}

	public static function PasswordLogin(mysqli $db, bool $remember): self {
		if (!isset($_POST['username'], $_POST['password']))
			throw new DetailedException('username and password must be provided');
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		if (!$username || !$password)
			throw new DetailedException('username and password must be provided');
		try {
			$select = $db->prepare('select id, passwordhash from user where username=? and passwordhash is not null limit 1');
			$select->bind_param('s', $username);
			$select->execute();
			$select->bind_result($id, $hash);
			if ($select->fetch() && self::CheckPassword($password, $hash))
				return self::Login($db, 'password', $id, $remember);
			else
				return new self($db);  // anonymous
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error logging in user', $mse);
		}
	}

	public static function Login(mysqli $db, string $source, int $id, bool $remember): self {
		$_SESSION[self::SessionKey] = $id;
		$_SESSION['loginsource'] = $source;

		$user = new self($db);
		if ($user->IsLoggedIn()) {
			$user->UpdateLastLogin($db);
			if ($remember)
				$user->StartRememberSeries($db);
		}

		return $user;
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

	public function AddLogin(mysqli $db, string $site, string $id, ?string $name, ?string $url, ?string $avatar): void {
		// link this login's avatar if user doesn't currently have an avatar
		$linkAvatar = $avatar && ($this->Avatar == self::DefaultAvatar || !$this->Avatar);
		try {
			$insert = $db->prepare('insert into login (site, id, user, name, url, avatar, linkavatar) values (?, ?, ?, ?, ?, ?, ?)');
			$insert->bind_param('ssisssi', $site, $id, $this->ID, $name, $url, $avatar, $linkAvatar);
			$insert->execute();
			if ($linkAvatar)
				$this->UpdateAvatar($db, $avatar);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error adding login profile', $mse);
		}
	}

	public function UpdateLogin(mysqli $db, string $site, string $id, ?string $name, ?string $url, ?string $avatar): void {
		try {
			$update = $db->prepare('update login set name=?, url=?, avatar=? where site=? and id=? and user=? limit 1');
			$update->bind_param('ssisss', $name, $url, $avatar, $site, $id, $this->ID);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating login profile', $mse);
		}
	}

	public function UpdateAvatar(mysqli $db, string $avatar): void {
		try {
			$update = $db->prepare('update user set avatar=? where id=? limit 1');
			$update->bind_param('si', $avatar, $this->ID);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating avatar', $mse);
		}
	}

	/**
	 * update the last time the user logged in.
	 * @param integer $id user id who just logged in
	 */
	private function UpdateLastLogin(mysqli $db) {
		try {
			$insert = $db->prepare('update user set lastlogin=now() where id=? limit 1');
			$insert->bind_param('i', $this->ID);
			$insert->execute();
		} catch (mysqli_sql_exception) {
			// don't fail a login for this reason
		}
	}

	/**
	 * Checks a plain-text password against an encrypted password.
	 *
	 * @param string $password Plain-text password.
	 * @param string $hash Encrypted password.
	 * @return bool True if passwords match.
	 */
	private static function CheckPassword($password, $hash) {
		$len = strlen($hash);
		$saltpass = $password . substr($hash, 0, 8);
		if ($len == 96)  // currently using base64 SHA512 with 8-character base64 salt for 96 characters total
			return base64_encode(hash('sha512', $saltpass, true)) == substr($hash, 8);
		// TODO:  convert less secure passwords to new format
		if ($len == 48)  // previously used hexadecimal SHA1 with 8-character hexadecimal salt for 48 characters total
			return sha1($saltpass) == substr($hash, 8);
		if ($len == 32)  // originally used unsalted MD5
			return md5($password) == $hash;
		return false;
	}

	private function StartRememberSeries(mysqli $db): void {
		try {
			do {
				$series = base64_encode(openssl_random_pseudo_bytes(12));
				if ($chk = $db->query('select 1 from remember where series=\'' . $db->real_escape_string($series) . '\' limit 1'))
					$chk = $chk->fetch_object();
			} while ($chk);
			$this->UpdateRememberSeries($db, $series);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error starting remembered login series', $mse);
		}
	}

	private function UpdateRememberSeries(mysqli $db, string $series): void {
		try {
			$token = openssl_random_pseudo_bytes(32);
			$tokenhash = base64_encode(hash('sha512', $token, true));
			$cookieLife = self::CookieLife;
			$replace = $db->prepare('replace into remember (series, tokenhash, expires, user) values (?, ?, date_add(now(), interval ? second), ?)');
			$replace->bind_param('ssii', $series, $tokenhash, $cookieLife, $this->ID);
			$replace->execute();
			$token = base64_encode($token);
			setcookie(self::CookieName, "$series:$token", time() + self::CookieLife, '/');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating remembered login series', $mse);
		}
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
		$select = $db->prepare('select tokenhash, unix_timestamp(expires), user from remember where series=? limit 1');
		$select->bind_param('s', $series);
		$select->execute();
		$select->bind_result($tokenhash, $expires, $id);
		if ($select->fetch() && $expires >= time())
			if ($tokenhash == base64_encode(hash('sha512', base64_decode($token), true)))
				return $id;
			else  // token doesn't match
				$this->ClearAllUserRememberSeries($db);
		else  // expired
			self::ClearExpiredRememberSeries($db);
		return 0;
	}

	private static function ClearExpiredRememberSeries(mysqli $db): void {
		try {
			$delete = $db->prepare('delete from remember where expires<now()');
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			// not important enough to stop what we're doing
		}
	}

	private function ClearAllUserRememberSeries(mysqli $db): void {
		try {
			$delete = $db->prepare('delete from remember where user=? or expires>now()');
			$delete->bind_param('i', $this->ID);
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error clearing all user remembered login series', $mse);
		}
	}

	private function ClearRememberSeries(mysqli $db, string $series): void {
		try {
			$delete = $db->prepare('delete from remember where series=? or expires>now()');
			$delete->bind_param('s', $series);
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error clearing remembered login series', $mse);
		}
	}

	public function Logout(mysqli $db): void {
		unset($_SESSION['loginsource']);
		unset($_SESSION[self::SessionKey]);
		if (isset($_COOKIE[self::CookieName])) {
			$this->ClearRememberSeries($db, explode(':', $_COOKIE[self::CookieName])[0]);
			setcookie(self::CookieName, '', time() - 60, '/');
		}
	}
}

class Friend {
	public static function Add(mysqli $db, CurrentUser $user, int $friend): void {
		try {
			$insert = $db->prepare('replace into friend (fan, friend) values (?, ?)');
			$insert->bind_param('ii', $user->ID, $friend);
			$insert->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error adding friend', $mse);
		}
	}

	public static function Remove(mysqli $db, CurrentUser $user, int $friend): void {
		try {
			$delete = $db->prepare('delete from friend where fan=? and friend=? limit 1');
			$delete->bind_param('ii', $user->ID, $friend);
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error removing friend', $mse);
		}
	}
}
