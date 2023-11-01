<?php
require_once 'environment.php';

class IndexPhoto {
	private const ListLimit = 24;

	public $ID;
	public $Title;

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
}
