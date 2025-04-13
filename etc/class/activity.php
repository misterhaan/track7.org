<?php
require_once 'environment.php';

class UserActivity {
	private const ListLimit = 12;

	public string $Type;
	public ?TimeTagData $Instant;
	public string $URL;
	public string $Title;
	public string $Verb;

	private function __construct(CurrentUser $user, string $type, int $instant, string $url, string $title, string $verb) {
		$this->Type = $type;
		require_once 'formatDate.php';
		$this->Instant = $instant > 9000 ? new TimeTagData($user, 'ago', $instant, FormatDate::Long) : null;
		$this->URL = $url;
		$this->Title = $title;
		$this->Verb = $verb;
	}

	public static function List(mysqli $db, CurrentUser $user, string $username, int $skip): ActivityList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select a.type, unix_timestamp(a.instant), a.url, a.title, a.verb from activity as a left join user as u on u.id=a.author where u.username=? order by instant desc limit ? offset ?');
			$select->bind_param('sii', $username, $limit, $skip);
			$select->execute();
			$select->bind_result($type, $instant, $url, $title, $verb);
			$result = new ActivityList();
			while ($select->fetch())
				if (count($result->Activity) < self::ListLimit)
					$result->Activity[] = new self($user, $type, $instant, $url, $title, $verb);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up user activity', $mse);
		}
	}
}

class SiteActivity {
	private const ListLimit = 12;

	public string $Type;
	public ?TimeTagData $Instant;
	public string $URL;
	public string $Title;
	public string $Name;
	public string $Contact;
	public string $Preview;
	public bool $HasMore;

	private function __construct(CurrentUser $user, string $type, int $instant, string $url, string $title, string $name, string $contact, string $preview, bool $hasmore) {
		$this->Type = $type;
		require_once 'formatDate.php';
		$this->Instant = $instant > 9000 ? new TimeTagData($user, 'smart', $instant, FormatDate::Long) : null;
		$this->URL = $url;
		$this->Title = $title;
		$this->Name = $name;
		$this->Contact = $contact;
		$this->Preview = $preview;
		$this->HasMore = $hasmore;
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip): ActivityList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select type, unix_timestamp(instant), url, title, name, contact, preview, hasmore from activity order by instant desc limit ? offset ?');
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($type, $instant, $url, $title, $name, $contact, $preview, $hasmore);
			$result = new ActivityList();
			while ($select->fetch())
				if (count($result->Activity) < self::ListLimit)
					$result->Activity[] = new self($user, $type, $instant, $url, $title, $name, $contact, $preview, $hasmore);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up site activity', $mse);
		}
	}
}

class ActivityList {
	/**
	 * @var Activity[] Group of site activity loaded
	 */
	public array $Activity = [];
	/**
	 * Whether there are more site activity to load
	 */
	public bool $HasMore = false;
}
