<?php
require_once 'environment.php';

class IndexPhoto {
	private const ListLimit = 24;

	public string $ID;
	public string $Title;

	private function __construct(string $id, string $title) {
		$this->ID = $id;
		$this->Title = $title;
	}

	/**
	 * Get the next group of photos to list.
	 * @param $db Database connection
	 * @param $tagName Only list photos with this tag, or list all photos if blank
	 * @param $skip Number of photos to skip (zero unless getting more)
	 * @return PhotoList Next group of photos
	 */
	public static function List(mysqli $db, string $tagName = '', int $skip = 0): PhotoList {
		$limit = self::ListLimit + 1;
		$select = $tagName
			? 'select ph.id, ps.title from photo as ph join post_tag as pt on pt.post=ph.post and pt.tag=? left join post as ps on ps.id=ph.post order by ps.instant desc limit ? offset ?'
			: 'select ph.id, ps.title from photo as ph left join post as ps on ps.id=ph.post order by ps.instant desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			if ($tagName)
				$select->bind_param('sii', $tagName, $limit, $skip);
			else
				$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $title);
			$result = new PhotoList();
			while ($select->fetch())
				if (count($result->Photos) < self::ListLimit)
					$result->Photos[] = new IndexPhoto($id, $title);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up photos', $mse);
		}
	}
}

/**
 * Partial list of photos
 */
class PhotoList {
	/**
	 * @var IndexPhoto[] Group of photos loaded
	 */
	public array $Photos = [];
	/**
	 * Whether there are more photos to load
	 */
	public bool $HasMore = false;
}

class Photo extends IndexPhoto {
	public int $Post;
	public string $Youtube;
	public int $Instant;
	public ?int $Taken;
	public int $Year;
	public string $Story;

	public function __construct(string $id, int $post, string $youtube, string $title, int $instant, ?int $taken, int $year, string $story) {
		$this->ID = $id;
		$this->Post = $post;
		$this->Youtube = $youtube;
		$this->Title = $title;
		$this->Instant = $instant;
		$this->Taken = $taken;
		$this->Year = $year;
		$this->Story = $story;
	}

	public static function FromQueryString(mysqli $db): ?Photo {
		if (!isset($_GET['photo']) || !$_GET['photo'])
			return null;
		try {
			$select = $db->prepare('select ph.id, ph.post, ph.youtube, ps.title, unix_timestamp(ps.instant), unix_timestamp(ph.taken), ph.year, ph.story from photo as ph left join post as ps on ps.id=ph.post where ph.id=? limit 1');
			$select->bind_param('s', $_GET['photo']);
			$select->execute();
			$select->bind_result($id, $post, $youtube, $title, $instant, $taken, $year, $story);
			if ($select->fetch())
				return new Photo($id, $post, $youtube, $title, $instant, $taken, $year, $story);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up photo', $mse);
		}
		return null;
	}
}

class EditPhoto extends Photo {
	public string $Tags;

	public function __construct(string $id, string $youtube, string $title, ?int $taken, int $year, string $story, string $tags) {
		parent::__construct($id, 0, $youtube, $title, 0, $taken, $year, $story);
		$this->Tags = $tags;
	}

	public static function FromID(mysqli $db, string $id): ?EditPhoto {
		try {
			$select = $db->prepare('select ph.id, ph.youtube, ps.title, unix_timestamp(ph.taken), ph.year, coalesce(nullif(ph.storymd, \'\'), ph.story), group_concat(pt.tag) from photo as ph left join post as ps on ps.id=ph.post left join post_tag as pt on pt.post=ph.post where ph.id=? group by ph.id');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $youtube, $title, $taken, $year, $story, $tags);
			if ($select->fetch())
				return new EditPhoto($id, $youtube, $title, $taken, $year, $story, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up photo', $mse);
		}
		return null;
	}

	public static function FromPOST(CurrentUser $user): EditPhoto {
		if (!isset($_POST['id'], $_POST['title'], $_POST['story']))
			throw new DetailedException('id, title, and story are required');
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($story = trim($_POST['story'])))
			throw new DetailedException('story cannot be blank');
		$youtube = isset($_POST['youtube']) ? trim($_POST['youtube']) : '';
		$taken = null;
		if (isset($_POST['taken'])) {
			require_once 'formatDate.php';
			$taken = FormatDate::LocalToTimestamp(trim($_POST['taken']), $user);
			if ($taken === false)
				$taken = null;
		}
		$year = isset($_POST['year']) && is_numeric($_POST['year']) ? +$_POST['year'] : 0;
		$deltags = isset($_POST['deltags']) ? trim($_POST['deltags']) : '';
		$addtags = isset($_POST['addtags']) ? trim($_POST['addtags']) : '';
		return new EditPhoto($id, $youtube, $title, $taken, $year, $story, "-$deltags+$addtags");
	}

	public static function IdAvailable(mysqli $db, string $oldID, string $newID): ValidationResult {
		if ($oldID == $newID)
			return new ValidationResult('valid');
		try {
			$select = $db->prepare('select ps.title from photo as ph left join post as ps on ps.id=ph.post where ph.id=?');
			$select->bind_param('s', $newID);
			$select->execute();
			$select->bind_result($title);
			if ($select->fetch())
				return new ValidationResult('invalid', "“{$newID}” already in use by $title.");
			return new ValidationResult('valid');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking photo id', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Story);
			$update = $db->prepare('update photo set id=?, youtube=?, taken=from_unixtime(?), year=?, story=?, storymd=? where id=? limit 1');
			$update->bind_param('ssiisss', $this->ID, $this->Youtube, $this->Taken, $this->Year, $html, $this->Story, $oldID);
			$update->execute();

			$select = $db->prepare('select post from photo where id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($this->Post);
			while ($select->fetch());

			$update = $db->prepare('update post set title=?, url=concat(\'/album/\', ?), preview=concat(\'<p><img class=photo src="/album/photos/\', ?, \'.jpeg"></p>\') where id=? limit 1');
			$update->bind_param('sssi', $this->Title, $this->ID, $this->ID, $this->Post);
			$update->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::RemoveFromPost($db, $this->Post, $tags[0]);
			Tag::AddToPost($db, $this->Post, 'album', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating photo', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (now(), ?, \'album\', concat(\'/album/\', ?), 1, concat(\'<p><img class=photo src="/album/photos/\', ?, \'.jpeg"></p>\'), 1)');
			$insert->bind_param('sss', $this->Title, $this->ID, $this->ID);
			$insert->execute();
			$this->Post = $db->insert_id;

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Story);
			$insert = $db->prepare('insert into photo (id, post, youtube, taken, year, story, storymd) values (?, ?, ?, from_unixtime(?), ?, ?, ?)');
			$insert->bind_param('sisiiss', $this->ID, $this->Post, $this->Youtube, $this->Taken, $this->Year, $html, $this->Story);
			$insert->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::AddToPost($db, $this->Post, 'album', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new photo', $mse);
		}
	}
}
