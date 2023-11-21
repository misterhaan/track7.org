<?php
require_once 'environment.php';

class IndexArt {
	private const ListLimit = 24;

	public string $ID;
	public string $Ext;

	protected function __construct(string $id, string $ext) {
		$this->ID = $id;
		$this->Ext = $ext;
	}

	/**
	 * Get the next group of art to list.
	 * @param $db Database connection
	 * @param $tagName Only list art with this tag, or list all art if blank
	 * @param $skip Number of art to skip (zero unless getting more)
	 * @return PhotoList Next group of art
	 */
	public static function List(mysqli $db, string $tagName = '', int $skip = 0): ArtList {
		$limit = self::ListLimit + 1;
		$select = $tagName
			? 'select a.id, a.ext from art as a join post_tag pt on pt.post=a.post and pt.tag=? left join post as p on p.id=a.post order by p.instant desc, p.id desc limit ? offset ?'
			: 'select a.id, a.ext from art as a left join post as p on p.id=a.post order by p.instant desc, p.id desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			if ($tagName)
				$select->bind_param('sii', $tagName, $limit, $skip);
			else
				$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $ext);
			$result = new ArtList();
			while ($select->fetch())
				if (count($result->Art) < self::ListLimit)
					$result->Art[] = new IndexArt($id, $ext);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up art', $mse);
		}
	}
}

/**
 * Partial list of art
 */
class ArtList {
	/**
	 * @var IndexArt[] Group of art loaded
	 */
	public array $Art = [];
	/**
	 * Whether there are more art to load
	 */
	public bool $HasMore = false;
}

class Art extends IndexArt {
	public string $Title;
	public int $Post;
	public string $Deviation;
	public ?int $Instant;
	public string $Description;
	public float $Rating;
	public int $VoteCount;
	public int $Vote;

	protected function __construct(string $id, string $ext, string $title, int $post, string $deviation, ?int $instant, string $description, float $rating, int $votes, int $vote) {
		parent::__construct($id, $ext);
		$this->Title = $title;
		$this->Post = $post;
		$this->Deviation = $deviation;
		$this->Instant = $instant;
		$this->Description = $description;
		$this->Rating = $rating;
		$this->VoteCount = $votes;
		$this->Vote = $vote;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?Art {
		if (!isset($_GET['art']) || !$_GET['art'])
			return null;
		$ip = $user->IsLoggedIn() ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
		try {
			$select = $db->prepare('select a.id, a.ext, p.title, a.post, a.deviation, unix_timestamp(p.instant), a.html, ifnull(r.rating, 3.0), ifnull(r.votecount, 0), ifnull(v.vote, 0) from art as a left join post as p on p.id=a.post left join rating as r on r.post=p.id left join vote as v on v.post=p.id and v.user=? and v.ip=inet_aton(?) where a.id=? limit 1');
			$select->bind_param('iss', $user->ID, $ip, $_GET['art']);
			$select->execute();
			$select->bind_result($id, $ext, $title, $post, $deviation, $instant, $description, $rating, $votes, $vote);
			if ($select->fetch())
				return new Art($id, $ext, $title, $post, $deviation, $instant, $description, $rating, $votes, $vote);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up art', $mse);
		}
		return null;
	}
}

class EditArt extends Art {
	public string $Tags;

	public function __construct(string $id, string $ext, string $title, string $deviation, string $description, string $tags) {
		parent::__construct($id, $ext, $title, 0, $deviation, null, $description, 0, 0, 0);
		$this->Tags = $tags;
	}

	public static function FromID(mysqli $db, string $id): ?EditArt {
		try {
			$select = $db->prepare('select a.id, a.ext, p.title, a.deviation, coalesce(nullif(a.markdown, \'\'), a.html) as description, group_concat(pt.tag) from art as a left join post as p on p.id=a.post left join post_tag as pt on pt.post=p.id where a.id=? group by a.id');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $ext, $title, $deviation, $description, $tags);
			if ($select->fetch())
				return new EditArt($id, $ext, $title, $deviation, $description, $tags ? $tags : '');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up art', $mse);
		}
		return null;
	}

	public static function FromPOST(): EditArt {
		if (!isset($_POST['id'], $_POST['title'], $_POST['description']))
			throw new DetailedException('id, title, and description are required');
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($description = trim($_POST['description'])))
			throw new DetailedException('description cannot be blank');
		$deviation = isset($_POST['deviation']) ? trim($_POST['deviation']) : '';
		$deltags = isset($_POST['deltags']) ? trim($_POST['deltags']) : '';
		$addtags = isset($_POST['addtags']) ? trim($_POST['addtags']) : '';
		return new EditArt($id, '', $title, $deviation, $description, "-$deltags+$addtags");
	}

	public static function IdAvailable(mysqli $db, string $oldID, string $newID): ValidationResult {
		if ($oldID == $newID)
			return new ValidationResult('valid');
		try {
			$select = $db->prepare('select p.title from art as a left join post as p on p.id=a.post where a.id=?');
			$select->bind_param('s', $newID);
			$select->execute();
			$select->bind_result($title);
			if ($select->fetch())
				return new ValidationResult('invalid', "“{$newID}” already in use by $title.");
			return new ValidationResult('valid');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking art id', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Description);
			$update = $db->prepare('update art set id=?, ' . ($this->Ext ? 'ext=?, ' : '') . 'deviation=?, html=?, markdown=? where id=? limit 1');
			if ($this->Ext)
				$update->bind_param('ssssss', $this->ID, $this->Ext, $this->Deviation, $html, $this->Description, $oldID);
			else
				$update->bind_param('sssss', $this->ID, $this->Deviation, $html, $this->Description, $oldID);
			$update->execute();

			$select = $db->prepare('select post, ext from art where id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($this->Post, $this->Ext);
			while ($select->fetch());

			$update = $db->prepare('update post set title=?, url=concat(\'/art/\', ?), preview=concat(\'<p><img class=art src="/art/img/\', ?, \'.\', ?, \'"></p>\') where id=? limit 1');
			$update->bind_param('ssssi', $this->Title, $this->ID, $this->Ext, $this->ID, $this->Post);
			$update->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::RemoveFromPost($db, $this->Post, $tags[0]);
			Tag::AddToPost($db, $this->Post, 'art', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating art', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (now(), ?, \'art\', concat(\'/art/\', ?), 1, concat(\'<p><img class=art src="/art/img/\', ?, \'.\', ?, \'"></p>\'), 1)');
			$insert->bind_param('ssss', $this->Title, $this->ID, $this->ID, $this->Ext);
			$insert->execute();
			$this->Post = $db->insert_id;

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Description);
			$insert = $db->prepare('insert into art (id, post, ext, deviation, html, markdown) values (?, ?, ?, ?, ?, ?)');
			$insert->bind_param('sissss', $this->ID, $this->Post, $this->Ext, $this->Deviation, $html, $this->Description);
			$insert->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::AddToPost($db, $this->Post, 'art', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new art', $mse);
		}
	}
}
