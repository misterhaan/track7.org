<?php

use Dom\Mysql;

require_once 'environment.php';

abstract class Auth extends KeyMaster {
	public string $Name;

	public static function Provider(string $provider): self {
		$file = __DIR__ . "/auth/$provider.php";
		if (!file_exists($file))
			throw new DetailedException("authentication provider $provider not implemented.", "filename:  $file");
		require_once $file;
		$className = ucfirst($provider) . 'Auth';
		if (!class_exists($className))
			throw new DetailedException("authentication provider $provider not implemented correctly.", "className:  $className");
		return new $className();
	}

	public abstract function Begin(bool $remember, ?string $return): string;

	public abstract function Process(mysqli $db): ?AuthResult;

	public static function Register(mysqli $db, CurrentUser $user): void {
		if (!isset($_POST['csrf']) || !self::CheckCSRF($_POST['csrf']))
			throw new DetailedException('invalid or missing CSRF token');
		if (!isset($_POST['username']))
			throw new DetailedException('username must be specified');
		$username = trim($_POST['username']);
		$valid = User::IdAvailable($db, $user, $username);
		if (!$valid->IsValid)
			throw new DetailedException('invalid username', $valid->Message);
		$displayname = isset($_POST['displayname']) ? trim($_POST['displayname']) : null;
		if ($displayname) {
			$valid = User::NameAvailable($db, $user, $displayname);
			if (!$valid->IsValid)
				throw new DetailedException('invalid display name', $valid->Message);
		}
		$email = isset($_POST['email']) ? trim($_POST['email']) : null;
		if ($email) {
			$valid = ContactLink::Validate('email', $email);
			if (!$valid->IsValid)
				throw new DetailedException('invalid e-mail address', $valid->Message);
		}
		$website = isset($_POST['website']) ? trim($_POST['website']) : null;
		if ($website) {
			$valid = ContactLink::Validate('website', $website);
			if (!$valid->IsValid)
				throw new DetailedException('invalid website URL', $valid->Message);
		}
		$linkprofile = isset($_POST['linkprofile']) ? boolval($_POST['linkprofile']) && strtolower($_POST['linkprofile']) != false && isset($_SESSION['registering']['profile']) && $_SESSION['registering']['profile'] : false;
		$useavatar = isset($_POST['useavatar']) ? boolval($_POST['useavatar']) && strtolower($_POST['useavatar']) != false && isset($_SESSION['registering']['avatar']) && $_SESSION['registering']['avatar'] : false;
		$avatar = $useavatar ? $_SESSION['registering']['avatar'] : null;

		$db->begin_transaction();
		try {
			$insert = $db->prepare('insert into user (username, displayname, avatar) values (?, ?, ?)');
			$insert->bind_param('sss', $username, $displayname, $avatar);
			$insert->execute();
			$uid = $insert->insert_id;

			$insert = $db->prepare('insert into login (site, id, user, name, url, avatar, linkavatar) values (?, ?, ?, ?, ?, ?, ?)');
			$insert->bind_param(
				'ssisssi',
				$_SESSION['registering']['provider'],
				$_SESSION['registering']['id'],
				$uid,
				$_SESSION['registering']['displayname'],
				$_SESSION['registering']['profile'],
				$_SESSION['registering']['avatar'],
				$linkprofile
			);
			$insert->execute();

			require_once 'contact.php';
			if ($email)
				ContactLink::Add($db, $uid, 'email', $email);
			if ($website)
				ContactLink::Add($db, $uid, 'website', $website);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			$db->rollback();
			throw DetailedException::FromMysqliException('error registering user', $mse);
		}
	}

	/**
	 * Get the URL to return to after authentication.
	 * @param ?string $return requested URL to return to after authentication
	 * @return string URL to return to after authentication
	 */
	protected static function GetReturnURL(?string $return): string {
		if (!$return && isset($_SERVER['HTTP_REFERER']) && !str_contains($_SERVER['HTTP_REFERER'], '/user/via/'))
			$return = $_SERVER['HTTP_REFERER'];
		require_once 'formatUrl.php';
		return FormatURL::RelativeRootUrl($return);
	}

	/**
	 * Get the URL to redirect to after authentication.  This is the URL that the
	 * authentication provider will redirect to after authentication.
	 * @return string URL to redirect to after authentication
	 */
	protected function GetRedirectURL(): string {
		require_once 'formatUrl.php';
		return FormatURL::FullUrl("/user/via/{$this->Name}.php");
	}

	/**
	 * Get the CSRF token for this session.  If it doesn't exist, create it.
	 * @return string CSRF token
	 */
	public static function GetCSRF(): string {
		if (!isset($_SESSION['CSRF']))
			$_SESSION['CSRF'] = bin2hex(openssl_random_pseudo_bytes(16));
		return $_SESSION['CSRF'];
	}

	/**
	 * Check a returned value against the stored cross-site request forgery
	 * token.  the stored token will be deleted as part of the check.
	 * @param string $csrf returned value from the cross-site request.
	 * @return boolean whether the returned value matches the stored value
	 */
	protected static function CheckCSRF($csrf) {
		if (!isset($_SESSION['CSRF']))
			return false;
		$stored = $_SESSION['CSRF'];
		unset($_SESSION['CSRF']);
		return $csrf == $stored;
	}

	/**
	 * Ensures service connection information is available before continuing.
	 */
	protected function RequireServiceKeys(string $keyClass, array $keys): void {
		self::RequireKeys();
		if (!class_exists($keyClass))
			throw new DetailedException("$this->Name keys not specified or incomplete", "class:  $keyClass");
		foreach ($keys as $key) {
			if (!defined("$keyClass::$key"))
				throw new DetailedException("$this->Name keys not specified or incomplete", "key:  $key");
		}
	}
}

class AuthResult {
	public bool $IsValid = false;
	public bool $Remember = false;
	public string $Continue = '/';
	public ?AuthUser $User = null;
	public ?LoginProfile $LoginMatch = null;
}

class AuthUser {
	public string $ID;
	public ?string $ProfileURL = null;
	public ?string $Avatar = null;
	public ?string $Username = null;
	public ?string $Email = null;
	public ?string $Website = null;
	public ?string $DisplayName = null;

	public function __construct(string $id) {
		$this->ID = $id;
	}
}

class LoginProfile {
	public string $Site;
	public string $ID;
	public string $UserID;
	public string $Name;
	public ?string $URL;
	public ?string $Avatar;
	public bool $LinkAvatar;

	public function __construct(string $site, string $id, string $user, string $name, ?string $url, ?string $avatar, bool $linkAvatar) {
		$this->Site = $site;
		$this->ID = $id;
		$this->UserID = $user;
		$this->Name = $name;
		$this->URL = $url;
		$this->Avatar = $avatar;
		$this->LinkAvatar = $linkAvatar;
	}

	public static function Find(mysqli $db, string $site, string $id): ?self {
		try {
			$select = $db->prepare('select site, id, user, name, url, avatar, linkavatar from login where site=? and id=?');
			$select->bind_param('ss', $site, $id);
			$select->execute();
			$select->bind_result($site, $id, $user, $name, $url, $avatar, $linkAvatar);
			if ($select->fetch())
				return new self($site, $id, $user, $name, $url, $avatar, $linkAvatar);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up login profile', $mse);
		}
		return null;
	}
}
