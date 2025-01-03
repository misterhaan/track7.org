<?php
require_once 'environment.php';
require_once 'formatDate.php';

class Update {
	private const ListLimit = 24;

	public int $ID;
	public TimeTagData $Instant;
	public string $HTML;
	public int $Comments;

	private function __construct(CurrentUser $user, int $id, int $instant, string $html, int $comments = 0) {
		$this->ID = $id;
		$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->HTML = $html;
		$this->Comments = $comments;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['id']))
			return null;
		$id = +$_GET['id'];
		try {
			$select = $db->prepare('select id, unix_timestamp(instant), preview from post where id=? and subsite=\'updates\' limit 1');
			$select->bind_param('i', $id);
			$select->execute();
			$select->bind_result($id, $instant, $html);
			if ($select->fetch())
				return new self($user, $id, $instant, $html);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up site update', $mse);
		}
		return null;
	}

	public static function FromPOST(CurrentUser $user): self {
		if (!isset($_POST['markdown']))
			throw new DetailedException('update is required.');
		$markdown = trim($_POST['markdown']);
		if (!$markdown)
			throw new DetailedException('update cannot be blank.');
		require_once 'formatText.php';
		require_once 'formatDate.php';
		$html = FormatText::Markdown($markdown);
		$instant = isset($_POST['posted']) ? trim($_POST['posted']) : '';
		$instant = $instant ? FormatDate::LocalToTimestamp($instant, $user) : time();
		if ($instant === false)
			throw new DetailedException('invalid date');
		return new self($user, -1, $instant, $html);
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip = 0): UpdateList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select p.id, unix_timestamp(p.instant), p.preview, count(c.instant) from post as p left join comment as c on c.post=p.id where p.subsite=\'updates\' group by p.id order by p.instant desc limit ?, ?');
			$select->bind_param('ii', $skip, $limit);
			$select->execute();
			$select->bind_result($id, $instant, $html, $comments);
			$result = new UpdateList();
			while ($select->fetch())
				if (count($result->Updates) < self::ListLimit)
					$result->Updates[] = new self($user, $id, $instant, $html, $comments);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up site updates', $mse);
		}
	}

	public function Save(mysqli $db): void {
		$posted = strtotime($this->Instant->DateTime);
		try {
			$insert = $db->prepare('insert into post (instant, title, subsite, preview, url, author) values (from_unixtime(?), \'track7 update\', \'updates\', ?, \'/updates/-1\', 1)');
			$insert->bind_param('is', $posted, $this->HTML);
			$insert->execute();
			$this->ID = $insert->insert_id;
			$update = $db->prepare('update post set url=concat(\'/updates/\', ?) where id=?');
			$update->bind_param('ii', $this->ID, $this->ID);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving site update', $mse);
		}
	}
}

/**
 * Partial list of site updates
 */
class UpdateList {
	/**
	 * @var Update[] Group of updates loaded
	 */
	public array $Updates = [];
	/**
	 * Whether there are more updates to load
	 */
	public bool $HasMore = false;
}
