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

	private static function ExpandURL(string $type, string $contact): string {
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
			case 'twitter':
				return 'https://twitter.com/' . $contact;
			case 'website':
				return $contact;
			default:
				throw new DetailedException('unknown contact type “' . $type . '”');
		}
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
