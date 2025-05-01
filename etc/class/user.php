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
				$select = $db->prepare('select timebase!=\'gmt\', timeoffset from settings where id=? limit 1');
				$select->bind_param('i', $this->ID);
				$select->execute();
				$select->bind_result($this->DST, $this->TzOffset);
				$select->fetch();
			} catch (mysqli_sql_exception $mse) {
				// TODO:  re-enable after settings table has finished migrating
				//throw DetailedException::FromMysqliException('error looking up user settings', $mse);
			}
			try {
				// TODO:  migrate messages table
				$select = $db->prepare('select count(1) from users_messages as m left join users_conversations as c on c.id=m.conversation where (c.thisuser=? or c.thatuser=?) and m.author!=? and m.hasread=false');
				$select->bind_param('iii', $this->ID, $this->ID, $this->ID);
				$select->execute();
				$select->bind_result($this->UnreadMsgs);
				$select->fetch();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error counting unread messages', $mse);
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

	public function GetProfileSettings(mysqli $db): ProfileSettings {
		$settings = new ProfileSettings($this->Username);
		$settings->DisplayName = $this->DisplayName == $this->Username ? '' : $this->DisplayName;
		$settings->Avatar = $this->Avatar;
		if (substr($this->Avatar, 0, 13) == '/user/avatar/')
			$settings->AvatarOptions[] = new AvatarOption('current', 'current uploaded avatar', $this->Avatar);
		try {
			$select = $db->prepare('select site, id, name, avatar from login where user=? and avatar is not null');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($site, $id, $name, $avatar);
			while ($select->fetch())
				$settings->AvatarOptions[] = new AvatarOption("$site/$id", "link to $site account $name", $avatar);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up avatars from external logins', $mse);
		}
		try {
			$select = $db->prepare('select contact from contact where user=? and type=\'email\' limit 1');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($email);
			if ($select->fetch())
				$settings->AvatarOptions[] = new AvatarOption('gravatar', 'gravatar for ' . $email, self::BulidGravatarUrl($email));
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up email', $mse);
		}
		$settings->AvatarOptions[] = new AvatarOption('none', 'default anonymous picture', self::DefaultAvatar);
		return $settings;
	}

	public function SaveProfileSettings(mysqli $db, string $username, string $displayname, string $avatarsource): void {
		$changed = false;
		$avatarsite = $avatarid = null;
		if ($changed = $username != $this->Username) {
			$available = self::IdAvailable($db, $this, $username);
			if ($available->State == 'invalid')
				throw new DetailedException($available->Message);
		}
		if ($displayname && $displayname != $this->DisplayName) {
			$changed = true;
			if ($displayname == $username)
				$displayname = '';
			$available = self::NameAvailable($db, $this, $displayname);
			if ($available->State == 'invalid')
				throw new DetailedException($available->Message);
		}
		$avatar = $this->Avatar;
		switch ($avatarsource) {
			case 'current':
				if ($this->Avatar == self::DefaultAvatar)
					$avatar =  null;
				break;
			case 'none':
				$avatar = null;
				if ($this->Avatar != self::DefaultAvatar)
					$changed = true;
				break;
			case 'gravatar':
				$select = $db->prepare('select contact from contact where user=? and type=\'email\' limit 1');
				$select->bind_param('i', $this->ID);
				$select->execute();
				$select->bind_result($email);
				if (!$select->fetch())
					throw new DetailedException('gravatar cannot be used without an email address');
				$select->close();
				$avatar = self::BulidGravatarUrl($email);
				if ($avatar != $this->Avatar)
					$changed = true;
				break;
			default:
				list($avatarsite, $avatarid) = explode('/', $avatarsource, 2);
				$select = $db->prepare('select avatar, linkavatar from login where site=? and id=? and user=? limit 1');
				$select->bind_param('ssi', $avatarsite, $avatarid, $this->ID);
				$select->execute();
				$select->bind_result($avatar, $linkavatar);
				if (!$select->fetch())
					throw new DetailedException('could not find login for ' . $avatarsite . ' id ' . $avatarid);
				$select->close();
				if (!$avatar)
					throw new DetailedException('login for ' . $avatarsite . ' id ' . $avatarid . ' does not have an avatar');
				if ($avatar != $this->Avatar || !$linkavatar)
					$changed = true;
				break;
		}
		if ($changed) {
			try {
				$db->begin_transaction();
				$update = $db->prepare('update user set username=?, displayname=?, avatar=? where id=? limit 1');
				$update->bind_param('sssi', $username, $displayname, $avatar, $this->ID);
				$update->execute();
				$update->close();
				$update = $db->prepare('update login set linkavatar=if(site=? and id=?,true,false) where user=?');
				$update->bind_param('ssi', $avatarsite, $$avatarid, $this->ID);
				$update->execute();
				$db->commit();
				if (substr($avatar, 0, 13) != '/user/avatar/') {
					$avatardir = $_SERVER['DOCUMENT_ROOT'] . '/user/avatar/';
					$pngavatar = "$avatardir$username.png";
					if (file_exists($pngavatar))
						@unlink($pngavatar);
					$jpgavatar = "$avatardir$username.jpg";
					if (file_exists($jpgavatar))
						@unlink($jpgavatar);
				}
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error updating user profile', $mse);
			}
		}
	}

	private static function BulidGravatarUrl(string $email): string {
		return 'https://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?s=128&d=robohash';
	}

	public function GetTimeSettings(): TimeSettings {
		require_once 'formatDate.php';
		$settings = new TimeSettings();
		$settings->CurrentTime = FormatDate::Local('g:i a', time(), $this);
		$settings->DST = $this->DST;
		return $settings;
	}

	public function SaveTimeSettings(mysqli $db, string $currenttime, bool $dst): void {
		$serverTime = time();
		$localTime = $dst ? strtotime($currenttime) : strtotime($currenttime . ' GMT');
		$offset = round(($localTime - $serverTime) / 900) * 900;
		$timebase = $dst ? 'server' : 'gmt';
		try {
			$update = $db->prepare('update settings set timebase=?, timeoffset=? where user=? limit 1');
			$update->bind_param('sii', $timebase, $offset, $this->ID);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating user time settings', $mse);
		}
	}

	public function GetContactMethods(mysqli $db): array {
		try {
			$select = $db->prepare('select type, contact, visibility from contact where user=?');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($type, $contact, $visibility);
			$result = [];
			while ($select->fetch())
				$result[] = new ContactMethod($type, $contact, $visibility);
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up contact methods', $mse);
		}
	}

	public function SaveContactMethods(mysqli $db, array $contacts): void {
		try {
			$db->begin_transaction();

			$delete = $db->prepare('delete from contact where user=?');
			$delete->bind_param('i', $this->ID);
			$delete->execute();
			$delete->close();

			$insert = $db->prepare('insert into contact (user, type, contact, visibility) values (?, ?, ?, ?)');
			$insert->bind_param('isss', $this->ID, $type, $value, $visibility);
			foreach ($contacts as $contact) {
				$type = $contact->type;
				$value = $contact->value;
				$visibility = $contact->visibility;
				require_once 'contact.php';
				$valid = ContactLink::Validate($type, $value);
				if ($valid->State == 'invalid')
					throw new DetailedException($valid->Message);
				if ($valid->NewValue)  // should have happened before submitting, so not worrying about updating the client if this happens
					$value = $valid->NewValue;
				$insert->execute();
			}
			$insert->close();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving contact methods', $mse);
		}
	}

	public function GetNotificationSettings(mysqli $db): NotificationSettings {
		try {
			$select = $db->prepare('select e.contact, s.emailnewmessage from settings as s left join contact as e on e.user=s.user and e.type=\'email\' where s.user=? limit 1');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($email, $emailnewmessage);
			$select->fetch();
			return new NotificationSettings($email, $emailnewmessage);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up notification settings', $mse);
		}
	}

	public function SaveNotificationSettings(mysqli $db, bool $emailnewmessage): void {
		try {
			$select = $db->prepare('select 1 from contact where user=? and type=\'email\' limit 1');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$hasemail = $select->fetch() ? true : false;
			$select->close();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking for an email address', $mse);
		}
		if (!$hasemail && $emailnewmessage)
			throw new DetailedException('you must have an e-mail address on file to receive notifications of new messages');
		try {
			$update = $db->prepare('update settings set emailnewmessage=? where user=? limit 1');
			$update->bind_param('ii', $emailnewmessage, $this->ID);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving notification settings', $mse);
		}
	}

	public function GetLoginSettings(mysqli $db): LoginSettings {
		try {
			$select = $db->prepare('select passwordhash from user where id=?');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($passwordhash);
			$select->fetch();
			$select->close();
			$result = new LoginSettings($passwordhash);

			$select = $db->prepare('select site, id, name, url, avatar from login where user=?');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($site, $id, $name, $url, $avatar);
			while ($select->fetch())
				$result->Accounts[] = new LoginAccount($site, $id, $name, $url, $avatar);

			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up login settings', $mse);
		}
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

	public function RemoveLogin(mysqli $db, string $site, string $id): void {
		try {
			$select = $db->prepare('select count(1) from login where user=? group by user');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($loginCount);
			$select->fetch();
			$select->close();

			if ($loginCount < 2) {
				$select = $db->prepare('select passwordhash from user where id=? limit 1');
				$select->bind_param('i', $this->ID);
				$select->execute();
				$select->bind_result($passwordhash);
				$select->fetch();
				$select->close();
				if ($passwordhash)
					throw new DetailedException('cannot delete your last login option.');
			}

			$delete = $db->prepare('delete from login where site=? and id=? and user=? limit 1');
			$delete->bind_param('ssi', $site, $id, $this->ID);
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error removing login profile', $mse);
		}
	}

	public function RemovePassword(mysqli $db): void {
		try {
			$select = $db->prepare('select count(1) from login where user=? group by user');
			$select->bind_param('i', $this->ID);
			$select->execute();
			$select->bind_result($loginCount);
			$select->fetch();
			$select->close();

			if ($loginCount < 1)
				throw new DetailedException('cannot delete your last login option.');

			$update = $db->prepare('update user set passwordhash=null where id=? limit 1');
			$update->bind_param('i', $this->ID);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error removing password', $mse);
		}
	}

	public function UpdateLogin(mysqli $db, string $site, string $id, ?string $name, ?string $url, ?string $avatar): void {
		try {
			$update = $db->prepare('update login set name=?, url=?, avatar=? where site=? and id=? and user=? limit 1');
			$update->bind_param('sssssi', $name, $url, $avatar, $site, $id, $this->ID);
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

class ProfileSettings {
	public string $Username;
	public string $DisplayName;
	public string $Avatar;
	public array $AvatarOptions = [];

	public function __construct(string $username) {
		$this->Username = $username;
	}
}

class AvatarOption {
	public string $ID;
	public string $Label;
	public string $ImageURL;

	public function __construct(string $id, string $label, string $imageURL) {
		$this->ID = $id;
		$this->Label = $label;
		$this->ImageURL = $imageURL;
	}
}

class TimeSettings {
	public string $CurrentTime;
	public bool $DST;
}

class ContactMethod {
	public string $Type;
	public string $Contact;
	public string $Visibility;

	public function __construct(string $type, string $contact, string $visibility) {
		$this->Type = $type;
		$this->Contact = $contact;
		$this->Visibility = $visibility;
	}
}

class NotificationSettings {
	public ?string $EmailAddress;
	public bool $EmailNewMessage;

	public function __construct(?string $email, bool $emailnewmessage) {
		$this->EmailAddress = $email;
		$this->EmailNewMessage = $emailnewmessage;
	}
}

class LoginSettings {
	public bool $HasPassword;
	public bool $PasswordUsesOldEncryption;
	public array $Accounts = [];

	public function __construct(?string $passwordhash) {
		$len = strlen($passwordhash);
		$this->HasPassword = $len > 0;
		$this->PasswordUsesOldEncryption = $len > 0 && $len < 96;
	}
}

class LoginAccount {
	public string $Site;
	public string $ID;
	public ?string $Name;
	public ?string $URL;
	public ?string $Avatar;

	public function __construct(string $site, string $id, ?string $name, ?string $url, ?string $avatar) {
		$this->Site = $site;
		$this->ID = $id;
		$this->Name = $name;
		$this->URL = $url;
		$this->Avatar = $avatar;
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
