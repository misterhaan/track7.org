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
