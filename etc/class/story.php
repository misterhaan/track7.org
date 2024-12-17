<?php
require_once 'environment.php';
require_once 'formatDate.php';

class IndexStory {
	private const ListLimit = 24;

	public string $ID;
	public ?TimeTagData $Instant = null;
	public string $Title;
	public string $Description;
	public int $NumStories;

	private function __construct(CurrentUser $user, string $id, int $instant, string $title, string $description, string $numStories) {
		$this->ID = $id;
		if ($instant > 1000) {
			require_once 'formatDate.php';
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		}
		$this->Title = $title;
		$this->Description = $description;
		$this->NumStories = $numStories;
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip): StoryList {
		$limit = self::ListLimit + 1;
		$select = 'select s.id, unix_timestamp(p.instant) as timestamp, p.title, p.preview as description, 0 as numStories from story as s left join post as p on p.id=s.post where s.series is null union select concat(s.id, \'/\') as id, unix_timestamp(max(p.instant)) as timestamp, s.title, s.html as description, count(1) as numStories from series as s left join story as st on st.series=s.id left join post as p on p.id=st.post group by s.id order by timestamp desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $title, $description, $numStories);
			$result = new StoryList();
			while ($select->fetch())
				if (count($result->Stories) < self::ListLimit)
					$result->Stories[] = new self($user, $id, $instant, $title, $description, $numStories);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up stories', $mse);
		}
	}

	public static function ListSeries(mysqli $db, CurrentUser $user, string $series): array {
		$select = 'select s.id, unix_timestamp(p.instant), p.title, p.preview from story as s left join post as p on p.id=s.post where s.series=? order by s.number';
		try {
			$select = $db->prepare($select);
			$select->bind_param('s', $series);
			$select->execute();
			$select->bind_result($id, $instant, $title, $description);
			$stories = [];
			while ($select->fetch())
				$stories[] = new self($user, $id, $instant, $title, $description, 0);
			return $stories;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up series stories', $mse);
		}
	}
}

/**
 * Partial list of stories
 */
class StoryList {
	/**
	 * @var IndexStory[] Group of stories loaded
	 */
	public array $Stories = [];
	/**
	 * Whether there are more stories to load
	 */
	public bool $HasMore = false;
}

class Story {
	public string $ID;
	public int $Post;
	public ?TimeTagData $Instant = null;
	public string $Title;
	public string $HTML;
	public ?string $SeriesID;
	public int $Number;
	public ?string $SeriesTitle;
	public int $NumStories;

	private function __construct(CurrentUser $user, string $id, int $post, string $title, int $instant, string $html, ?string $series, int $number, ?string $seriesTitle, int $numStories) {
		$this->ID = $id;
		$this->Post = $post;
		if ($instant > 1000) {
			require_once 'formatDate.php';
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		}
		$this->Title = $title;
		$this->HTML = $html;
		$this->SeriesID = $series;
		$this->Number = $number;
		$this->SeriesTitle = $seriesTitle;
		$this->NumStories = $numStories;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['story']) || !$_GET['story'])
			return null;
		$id = trim($_GET['story']);
		try {
			$select = $db->prepare('select s.id, s.post, p.title, unix_timestamp(p.instant), s.html, s.series, s.number, sr.title, count(1) from story as s left join post as p on p.id=s.post left join series as sr on sr.id=s.series left join story as ss on ss.series=sr.id where s.id=? group by s.id limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $post, $title, $instant, $html, $series, $number, $seriesTitle, $numStories);
			if ($select->fetch())
				return new self($user, $id, $post, $title, $instant, $html, $series, +$number, $seriesTitle, $numStories);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up story', $mse);
		}
		return null;
	}
}

class Series {
	public string $ID;
	public string $Title;
	public string $Description;

	private function __construct(string $id, string $title, string $description) {
		$this->ID = $id;
		$this->Title = $title;
		$this->Description = $description;
	}

	public static function FromQueryString(mysqli $db): ?self {
		if (!isset($_GET['series']) || !$_GET['series'])
			return null;
		$id = trim($_GET['series']);
		try {
			$select = $db->prepare('select id, title, html from series where id=? limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $title, $description);
			if ($select->fetch())
				return new self($id, $title, $description);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up series', $mse);
		}
		return null;
	}
}
