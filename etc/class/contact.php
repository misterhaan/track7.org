<?php
require_once 'environment.php';

class ContactLink {
	public string $Type;
	public string $URL;
	public string $Action;

	private function __construct(string $type, string $contact, string $displayname) {
		$this->Type = $type;
		$this->URL = self::ExpandURL($type, $contact);
		$this->Action = self::GetAction($type, $displayname);
	}

	public static function List(mysqli $db, CurrentUser $user, string $username): array {
		$loggedin = +$user->IsLoggedIn();
		try {
			$select = $db->prepare('select c.type, c.contact, ifnull(nullif(u.displayname,\'\'),u.username) from contact as c join user as u on u.id=c.user left join friend as f on f.fan=u.id and f.friend=? where u.username=? and (c.visibility=\'all\' or c.visibility=\'users\' and ? or c.visibility=\'friends\' and f.fan is not null or u.id=?)');
			$select->bind_param('isii', $user->ID, $username, $loggedin, $user->ID);
			$select->execute();
			$select->bind_result($type, $contact, $displayname);
			$contacts = [];
			while ($select->fetch())
				$contacts[] = new self($type, $contact, $displayname);
			return $contacts;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up user contact methods', $mse);
		}
	}

	/**
	 * Add a contact method for the specified user if they don't already have something for that contact method.
	 */
	public static function Add(mysqli $db, int $userID, string $type, string $url): void {
		if (!self::ValidType($type))
			throw new DetailedException('invalid contact type “' . $type . '”');
		try {
			$contact = self::CollapseURL($type, $url);
			$insert = $db->prepare('insert into contact (user, type, contact) select ?, ?, ? where not exists (select 1 from contact where user=? and type=?)');
			$insert->bind_param('issis', $userID, $type, $contact, $userID, $type);
			$insert->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error adding contact method', $mse);
		}
	}

	public static function Validate(string $type, string $value): ValidationResult {
		switch ($type) {
			case 'email':
				if (strtolower(substr($value, -12)) == '@example.com')
					return new ValidationResult('invalid', 'e-mail address is not required.  please don’t enter a fake one.');
				if (preg_match('/^[^@\s]+@[^\.@\s]+\.[^@\s]*[^\.@\s]$/', $value))
					return new ValidationResult('valid', '', $value);
				return new ValidationResult('invalid', 'doesn’t look like an e-mail address.');

			case 'website':
				$original = $value;
				if (!str_contains($value, '://'))
					$value = "http://$value";
				if (preg_match('/^https?:\/\/[^\.\/]+(\.[^\.\/]+)+$/i', $value))
					$value .= '/';
				if (substr($value, 0, 7) != 'http://' && substr($value, 0, 8) != 'https://')
					return new ValidationResult('invalid', 'website must use http or https.');
				stream_context_set_default(array('http' => array('method' => 'HEAD')));
				$headers = @get_headers($value, 1);
				if ($headers && +substr($headers[0], 9, 3) < 400)
					if ($value != $original)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'unable to reach website ' . $value . '.');

			case 'twitter':
				$original = $value;
				$value = self::CollapseURL('twitter', $value);
				if (preg_match('/^[A-Za-z0-9_]{1,15}$/', $value))
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid twitter username.  please enter your twitter username or the url to your twitter profile.');

			case 'facebook':
				$original = $value;
				$value = self::CollapseURL('facebook', $value);
				if (preg_match('/^[A-Za-z0-9\.]{5,}$/', $value))
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid facebook username.  please enter your facebook username or the url to your facebook profile.');

			case 'github':
				$original = $value;
				$value = self::CollapseURL('github', $value);
				if (preg_match('/^[A-Za-z0-9\-]{1,39}$/', $value))
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid github username.  please enter your github username or the url to your github profile.');

			case 'deviantart':
				$original = $value;
				$value = self::CollapseURL('deviantart', $value);
				if (preg_match('/^[A-Za-z\-]{3,20}$/', $value))
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid deviantart username.  please enter your deviantart username or the url to your deviantart profile.');

			case 'steam':
				$original = $value;
				$value = self::CollapseURL('steam', $value);
				if (preg_match('/^[0-9]+$/', $value) || preg_match('/^[A-Za-z0-9_]{1,32}$/', $value))
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid steam username.  please enter your steam username or the url to your steam profile.');

			case 'twitch':
				$original = $value;
				$value = self::CollapseURL('twitch', $value);
				if (strlen($value) >= 3)
					if ($original != $value)
						return new ValidationResult('valid', '', $value);
					else
						return new ValidationResult('valid');
				return new ValidationResult('invalid', 'invalid twitch username.  please enter your twitch username or the url to your twitch profile.');
		}
		throw new DetailedException('unsupported contact type “' . $type . '”');
	}

	private static function ValidType(string $type): bool {
		return in_array($type, ['deviantart', 'email', 'facebook', 'github', 'steam', 'twitch', 'twitter', 'website']);
	}

	public static function ExpandURL(string $type, string $contact): string {
		switch ($type) {
			case 'deviantart':
				return 'https://' . $contact . '.deviantart.com/';
			case 'email':
				return 'mailto:' . $contact;
			case 'facebook':
				return 'https://www.facebook.com/' . $contact;
			case 'github':
				return 'https://github.com/' . $contact;
			case 'steam':
				return 'https://steamcommunity.com/' . (preg_match('/^[0-9]+$/', $contact) ? 'profiles' : 'id') . '/' . $contact;
			case 'twitch':
				return 'https://www.twitch.tv/' . $contact;
			case 'twitter':
				return 'https://twitter.com/' . $contact;
			case 'website':
				return $contact;
			default:
				throw new DetailedException('unknown contact type “' . $type . '”');
		}
	}

	private static function CollapseURL(string $type, string $url): string {
		switch ($type) {
			case 'deviantart':
				if (preg_match('/^https?:\/\/([A-Za-z\-]{3,20})\.deviantart\.com/', $url, $match))
					return $match[1];
				break;
			case 'email':
				if (substr($url, 0, 7) == 'mailto:')
					return substr($url, 7);
				return $url;
			case 'facebook':
				if (preg_match('/^https?:\/\/www\.facebook\.com\/([A-Za-z0-9\.]{5,})(\?.*)?$/', $url, $match))
					return $match[1];
				break;
			case 'github':
				if (preg_match('/^https?:\/\/github\.com\/([A-Za-z0-9\-]{1,39})\/?$/', $url, $match))
					return $match[1];
				break;
			case 'steam':
				if (substr($url, 0, 36) == 'https://steamcommunity.com/profiles/')
					return substr($url, 36);
				if (substr($url, 0, 30) == 'https://steamcommunity.com/id/')
					return substr($url, 30);
				if (substr($url, 0, 35) == 'http://steamcommunity.com/profiles/')
					return substr($url, 35);
				if (substr($url, 0, 29) == 'http://steamcommunity.com/id/')
					return substr($url, 29);
				break;
			case 'twitch':
				if (preg_match('/^https?:\/\/(www\.)?twitch\.tv\/([A-Za-z0-9_]{3,})\/?$/', $url, $match))
					return $match[2];
				break;
			case 'twitter':
				if (preg_match('/^(https?:\/\/(twitter|x)\.com\/|@)([A-Za-z0-9_]{1,15})$/', $url, $match))
					return $match[3];
				if (substr($url, 0, 1) == '@')
					return substr($url, 1);
				break;
		}
		return $url;
	}

	private static function GetAction(string $type, string $displayname): string {
		switch ($type) {
			case 'email':
				return "send $displayname an email";
			case 'website':
				return 'view ' . $displayname . '’s website';
			default:
				return 'view ' . $displayname . '’s ' . $type . ' profile';
		}
	}
}
