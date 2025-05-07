<?php
require_once 'environment.php';

class IndexLego {
	private const ListLimit = 24;

	public string $ID;
	public string $Title;

	protected function __construct(string $id, string $title) {
		$this->ID = $id;
		$this->Title = $title;
	}

	/**
	 * Get the next group of lego models to list.
	 * @param $db Database connection
	 * @param $skip Number of lego models to skip (zero unless getting more)
	 * @return PhotoList Next group of lego models
	 */
	public static function List(mysqli $db, int $skip = 0): LegoList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select l.id, p.title from lego as l left join post as p on p.id=l.post order by p.instant desc limit ? offset ?');
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $title);
			$result = new LegoList();
			while ($select->fetch())
				if (count($result->Legos) < self::ListLimit)
					$result->Legos[] = new self($id, $title);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up lego models', $mse);
		}
	}
}

/**
 * Partial list of lego models
 */
class LegoList {
	/**
	 * @var IndexLego[] Group of lego models loaded
	 */
	public array $Legos = [];
	/**
	 * Whether there are more lego models to load
	 */
	public bool $HasMore = false;
}

class Lego extends IndexLego {
	public int $Post;
	public int $Instant;
	public int $Pieces;
	public string $Description;
	public float $Rating;
	public int $VoteCount;
	public int $Vote;

	private function __construct(string $id, string $title, int $post, int $instant, int $pieces, string $description, float $rating, int $voteCount, int $vote) {
		parent::__construct($id, $title);
		$this->Post = $post;
		$this->Instant = $instant;
		$this->Pieces = $pieces;
		$this->Description = $description;
		$this->Rating = $rating;
		$this->VoteCount = $voteCount;
		$this->Vote = $vote;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['model']) || !$_GET['model'])
			return null;
		$ip = $user->IsLoggedIn() ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
		try {
			$select = $db->prepare('select l.id, p.title, l.post, unix_timestamp(p.instant), l.pieces, l.html, ifnull(r.rating, 3.0), ifnull(r.votecount, 0), ifnull(v.vote, 0) from lego as l left join post as p on p.id=l.post left join rating as r on r.post=p.id left join vote as v on v.post=p.id and v.user=? and v.ip=inet_aton(?) where l.id=?');
			$select->bind_param('iss', $user->ID, $ip, $_GET['model']);
			$select->execute();
			$select->bind_result($id, $title, $post, $instant, $pieces, $description, $rating, $voteCount, $vote);
			if ($select->fetch())
				return new self($id, $title, $post, $instant, $pieces, $description, $rating, $voteCount, $vote);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up lego model', $mse);
		}
		return null;
	}
}

class EditLego extends IndexLego {
	public int $Pieces;
	public string $Description;

	private function __construct(string $id, string $title, int $pieces, string $description) {
		parent::__construct($id, $title);
		$this->Pieces = $pieces;
		$this->Description = $description;
	}

	public static function FromID(mysqli $db, string $id): ?self {
		try {
			$select = $db->prepare('select l.id, p.title, l.pieces, l.markdown from lego as l left join post as p on p.id=l.post where l.id=? limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $title, $pieces, $markdown);
			if ($select->fetch())
				return new self($id, $title, $pieces, $markdown);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up lego model', $mse);
		}
		return null;
	}

	public static function FromPOST(): self {
		if (!isset($_POST['id'], $_POST['title'], $_POST['pieces'], $_POST['description']))
			throw new DetailedException('id, title, pieces, and description are required', print_r($_POST, true));
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($pieces = +trim($_POST['pieces'])))
			throw new DetailedException('pieces must be non-zero');
		if (!($description = trim($_POST['description'])))
			throw new DetailedException('description cannot be blank');
		return new self($id, $title, $pieces, $description);
	}

	public static function IdAvailable(mysqli $db, string $oldID, string $newID): ValidationResult {
		require_once 'formatText.php';
		$cleanID = FormatText::CleanID($newID);
		if ($oldID == $cleanID)
			if ($newID == $cleanID)
				return new ValidationResult('valid');
			else
				return new ValidationResult('valid', '', $cleanID);

		try {
			$select = $db->prepare('select p.title from lego as l left join post as p on p.id=l.post where l.id=?');
			$select->bind_param('s', $cleanID);
			$select->execute();
			$select->bind_result($title);
			if ($select->fetch())
				return new ValidationResult('invalid', "“{$cleanID}” already in use by $title.");
			if ($newID == $cleanID)
				return new ValidationResult('valid');
			else
				return new ValidationResult('valid', '', $cleanID);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking lego id', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Description);
			$update = $db->prepare('update lego set id=?, html=?, markdown=?, pieces=? where id=? limit 1');
			$update->bind_param('sssis', $this->ID, $html, $this->Description, $this->Pieces, $oldID);
			$update->execute();

			$select = $db->prepare('select post from lego where id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($post);
			while ($select->fetch());

			$update = $db->prepare('update post set title=?, url=concat(\'/lego/\', ?), preview=concat(\'<p><img src="/lego/data/\', ?, \'-thumb.png"></p>\') where id=? limit 1');
			$update->bind_param('sssi', $this->Title, $this->ID, $this->ID, $post);
			$update->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating lego model', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (now(), ?, \'lego\', concat(\'/lego/\', ?), 1, concat(\'<p><img src="/lego/data/\', ?, \'-thumb.png"></p>\'), true)');
			$insert->bind_param('sss', $this->Title, $this->ID, $this->ID);
			$insert->execute();
			$post = $db->insert_id;

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Description);
			$insert = $db->prepare('insert into lego (id, post, html, markdown, pieces) values (?, ?, ?, ?, ?)');
			$insert->bind_param('sisss', $this->ID, $post, $html, $this->Description, $this->Pieces);
			$insert->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new lego model', $mse);
		}
	}
}
