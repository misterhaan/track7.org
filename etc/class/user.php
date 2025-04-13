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
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up basic user information', $mse);
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
				// TODO:  move settings and login to new tables / views
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
		try {
			$insert = $db->prepare('update user set lastlogin=now() where id=? limit 1');
			$insert->bind_param('i', $id);
			$insert->execute();
		} catch (mysqli_sql_exception) {
			// this should only fail if user table doesn't have the lastlogin column yet
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
		// TODO:  migrate remembered logins table
		$select = $db->prepare('select tokenhash, expires, user from login_remembered where series=? limit 1');
		$select->bind_param('s', $series);
		$select->execute();
		$select->bind_result($tokenhash, $expires, $id);
		if ($select->fetch() && $expires >= time())
			if ($tokenhash == base64_encode(hash('sha512', base64_decode($token), true)))
				return $id;
			else
				$this->ClearAllUserRememberSeries($db);
		return 0;
	}

	private function ClearAllUserRememberSeries(mysqli $db): void {
		// TODO:  migrate remembered logins table
		try {
			$delete = $db->prepare('delete from login_remembered where user=? or expires>?');
			$delete->bind_param('ii', $this->ID, time());
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error clearing all user remembered login series', $mse);
		}
	}

	private function ClearRememberSeries(mysqli $db, string $series): void {
		// TODO:  migrate remembered logins table
		try {
			$delete = $db->prepare('delete from login_remembered where series=? or expires>?');
			$delete->bind_param('si', $series, time());
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
