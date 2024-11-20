<?php
require_once 'environment.php';
require_once 'formatDate.php';

class IndexGuide {
	private const ListLimit = 24;

	public string $ID;
	public TimeTagData $Instant;
	public string $Posted;
	public string $Title;
	public string $Summary;
	public string $Level;
	public array $Tags = [];
	public int $Views;
	public int $Rating;
	public int $VoteCount;

	protected function __construct(CurrentUser $user, string $id, ?int $instant, int $updated, string $title, string $summary, string $level, string $tags, int $views, int $rating, int $voteCount) {
		$this->ID = $id;
		$this->Instant = new TimeTagData($user, 'smart', $updated, FormatDate::Long);
		$this->Posted = $instant == null || $updated == $instant ? '' : FormatDate::Local(FormatDate::Long, $instant, $user);
		$this->Title = $title;
		$this->Summary = $summary;
		$this->Level = $level;
		$this->Tags = explode(',', $tags);
		$this->Views = $views;
		$this->Rating = $rating;
		$this->VoteCount = $voteCount;
	}

	/**
	 * Get the next group of guides to list.
	 * @param $db Database connection
	 * @param $user Signed-in user
	 * @param $tagName Only list guides with this tag, or list all guides if blank
	 * @param $skip Number of guides to skip (zero unless getting more)
	 * @return GuideList Next group of guides
	 */
	public static function List(mysqli $db, CurrentUser $user, string $tagName = '', int $skip = 0): GuideList {
		$limit = self::ListLimit + 1;
		$select = $tagName
			? 'select g.id, unix_timestamp(p.instant), unix_timestamp(g.updated), p.title, p.preview, g.level, ifnull(group_concat(pt.tag),\'\'), g.views, ifnull(r.rating, 3), ifnull(r.votecount, 0) from guide as g left join post as p on p.id=g.post left join post_tag pt on pt.post=p.id left join rating as r on r.post=p.id join post_tag pt2 on pt2.post=p.id and pt2.tag=? where p.published=true group by g.id order by g.updated desc limit ? offset ?'
			: 'select g.id, unix_timestamp(p.instant), unix_timestamp(g.updated), p.title, p.preview, g.level, ifnull(group_concat(pt.tag),\'\'), g.views, ifnull(r.rating, 3), ifnull(r.votecount, 0) from guide as g left join post as p on p.id=g.post left join post_tag pt on pt.post=p.id left join rating as r on r.post=p.id where p.published=true group by g.id order by g.updated desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			if ($tagName)
				$select->bind_param('sii', $tagName, $limit, $skip);
			else
				$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $updated, $title, $summary, $level, $tags, $views, $rating, $voteCount);
			$result = new GuideList();
			while ($select->fetch())
				if (count($result->Guides) < self::ListLimit)
					$result->Guides[] = new self($user, $id, $instant, $updated, $title, $summary, $level, $tags, $views, $rating, $voteCount);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up guides', $mse);
		}
	}

	/**
	 * Get the full list of draft guides by the current user.
	 * @param $db Database connection
	 * @param $user Signed-in user
	 * @return TitledLink[] Draft guides
	 */
	public static function ListDrafts(mysqli $db, CurrentUser $user): array {
		$drafts = [];
		try {
			$select = $db->prepare('select g.id, p.title from guide as g left join post as p on p.id=g.post where p.published=false and p.author=? order by p.instant desc');
			$select->bind_param('i', $user->ID);
			$select->execute();
			$select->bind_result($id, $title);
			while ($select->fetch())
				$drafts[] = new TitledLink($title, $id);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up draft guides', $mse);
		}
		return $drafts;
	}
}

/**
 * Partial list of guides
 */
class GuideList {
	/**
	 * @var Guide[] Group of guides loaded
	 */
	public array $Guides = [];
	/**
	 * Whether there are more guides to load
	 */
	public bool $HasMore = false;
}

class Guide extends IndexGuide {
	public int $Post;
	public bool $Published;
	public int $Vote;

	private function __construct(CurrentUser $user, string $id, int $post, bool $published, ?int $instant, int $updated, string $title, string $summary, string $level, string $tags, int $views, int $rating, int $voteCount, int $vote) {
		parent::__construct($user, $id, $instant, $updated, $title, $summary, $level, $tags, $views, $rating, $voteCount);
		$this->Post = $post;
		$this->Published = $published;
		$this->Vote = $vote;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['url']) || !$_GET['url'])
			return null;
		$id = trim($_GET['url']);
		$ip = $user->IsLoggedIn() ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
		try {
			$select = $db->prepare('select g.id, p.id, p.published, unix_timestamp(p.instant), unix_timestamp(g.updated), p.title, p.preview, g.level, ifnull(group_concat(pt.tag),\'\'), g.views, ifnull(r.rating, 3), ifnull(r.votecount, 0), ifnull(v.vote, 0) from guide as g left join post as p on p.id=g.post left join post_tag pt on pt.post=p.id left join rating as r on r.post=p.id left join vote as v on v.post=p.id and (v.user=? and v.user>0 or ?=0 and v.ip=inet_aton(?)) where (published=true or p.author=?) and g.id=? group by g.id, v.post, v.user, v.ip limit 1');
			$select->bind_param('iisis', $user->ID, $user->ID, $ip, $user->ID, $id);
			$select->execute();
			$select->bind_result($id, $post, $published, $posted, $updated, $title, $preview, $level, $tags, $views, $rating, $votes, $vote);
			if ($select->fetch())
				return new self($user, $id, $post, $published, $posted, $updated, $title, $preview, $level, $tags, $views, $rating, $votes, $vote);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up guide', $mse);
		}
		return null;
	}

	public function LogView(mysqli $db): void {
		if ($this->Published)
			try {
				$update = $db->prepare('update guide set views=views+1 where id=? limit 1');
				$update->bind_param('s', $this->ID);
				$update->execute();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error logging guide view', $mse);
			}
	}
}

class Chapter {
	public int $Number;
	public string $Title;
	public string $HTML;

	private function __construct(int $number, string $title, string $html) {
		$this->Number = $number;
		$this->Title = $title;
		$this->HTML = $html;
	}

	public static function List(mysqli $db, string $guide) {
		try {
			$select = $db->prepare('select number, title, html from chapter where guide=? order by number');
			$select->bind_param('s', $guide);
			$select->execute();
			$select->bind_result($number, $title, $html);
			$chapters = [];
			while ($select->fetch())
				$chapters[] = new self($number, $title, $html);
			return $chapters;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up chapters', $mse);
		}
	}
}

class EditGuide {
	public string $ID;
	public int $Post;
	public bool $Published;
	public string $Title;
	public string $Summary;
	public string $Level;
	public string $Tags;
	public array $Chapters;

	private function __construct(string $id, int $post, bool $published, string $title, string $summary, string $level, string $tags) {
		$this->ID = $id;
		$this->Post = $post;
		$this->Published = $published;
		$this->Title = $title;
		$this->Summary = $summary;
		$this->Level = $level;
		$this->Tags = $tags;
	}

	public static function FromID(mysqli $db, string $id): ?self {
		try {
			$select = $db->prepare('select g.id, g.post, p.published, p.title, g.summary, g.level, ifnull(group_concat(pt.tag),\'\') from guide as g left join post as p on p.id=g.post left join post_tag as pt on pt.post=g.post where g.id=? group by g.id limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $post, $published, $title, $summary, $level, $tags);
			if ($select->fetch())
				return new self($id, $post, $published, $title, $summary, $level, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up guide', $mse);
		}
		return null;
	}

	public static function FromRequestObject(mysqli $db, object $request, string $currentID): self {
		self::VerifyRequestObject($db, $request, $currentID);

		$tags = '';
		if ($request->addTags)
			$tags .= '+' . $request->addTags;
		if ($request->delTags)
			$tags .= '-' . $request->delTags;

		$guide = new self($request->id, -1, false, $request->title, $request->summary, $request->level, $tags);

		$guide->Chapters = [];
		foreach ($request->chapters as $chapter)
			$guide->Chapters[] = new EditChapter($chapter->title, $chapter->markdown);

		return $guide;
	}

	private static function VerifyRequestObject(mysqli $db, object $request, string $currentID): void {
		if (!isset($request->id, $request->title, $request->summary, $request->level, $request->chapters))
			throw new DetailedException('id, title, summary, level, and chapters are required');

		if (!$request->id || !$request->title || !$request->summary)
			throw new DetailedException('id, title, and summary cannot be blank');
		$validID = self::IdAvailable($db, $currentID, $request->id);
		if ($validID->State == 'invalid')
			throw new DetailedException($validID->Message);
		if ($validID->NewValue)
			$request->id = $validID->NewValue;

		if (!in_array($request->level, ['beginner', 'intermediate', 'advanced']))
			throw new DetailedException('level must be either beginner, intermediate, or advanced');

		if (!count($request->chapters))
			throw new DetailedException('at least one chapter is required');

		foreach ($request->chapters as $chapter)
			if (!isset($chapter->title, $chapter->markdown) || !$chapter->title || !$chapter->markdown)
				throw new DetailedException('each chapter must have a title and markdown');
	}

	public function LoadChapters(mysqli $db): void {
		try {
			$select = $db->prepare('select title, markdown from chapter where guide=? order by number');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($title, $markdown);
			$this->Chapters = [];
			while ($select->fetch())
				$this->Chapters[] = new EditChapter($title, $markdown);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up guide chapters', $mse);
		}
	}

	public function Create(mysqli $db): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$summaryHTML = FormatText::Markdown($this->Summary);
			$insert = $db->prepare('insert into post (published, title, subsite, url, author, preview, hasmore) values (false, ?, \'guides\', concat(\'/guides/\', ?), 1, ?, true)');
			$insert->bind_param('sss', $this->Title, $this->ID, $summaryHTML);
			$insert->execute();
			$this->Post = $insert->insert_id;
			$insert->close();

			$insert = $db->prepare('insert into guide (id, post, summary, updated, level) values (?, ?, ?, now(), ?)');
			$insert->bind_param('siss', $this->ID, $this->Post, $this->Summary, $this->Level);
			$insert->execute();
			$insert->close();

			$this->SaveChapters($db);

			if ($this->Tags) {
				require_once 'tag.php';
				Tag::AddToPost($db, $this->Post, 'guides', ltrim($this->Tags, '+'));
			}

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new guide', $mse);
		}
	}

	public function Update(mysqli $db, string $id, bool $correctionsOnly) {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$summaryHTML = FormatText::Markdown($this->Summary);
			$update = $db->prepare('update post as p left join guide as g on p.id=g.post set p.title=?, p.url=concat(\'/guides/\', ?), preview=? where g.id=?');
			$update->bind_param('ssss', $this->Title, $this->ID, $summaryHTML, $id);
			$update->execute();
			$update->close();

			$delete = $db->prepare('delete from chapter where guide=?');
			$delete->bind_param('s', $id);
			$delete->execute();
			$delete->close();

			$update = 'update guide set id=?, summary=?';
			if (!$correctionsOnly)
				$update .= ', updated=now()';
			$update .= ', level=? where id=?';
			$update = $db->prepare($update);
			$update->bind_param('ssss', $this->ID, $this->Summary, $this->Level, $id);
			$update->execute();
			$update->close();

			$this->SaveChapters($db);

			$tags = explode('-', $this->Tags);
			$addtags = ltrim($tags[0], '+');
			$deltags = $tags[1];
			if ($addtags || $deltags) {
				$select = $db->prepare('select post from guide where id=? limit 1');
				$select->bind_param('s', $this->ID);
				$select->execute();
				$select->bind_result($this->Post);
				$select->fetch();
				$select->close();
			}
			if ($deltags) {
				require_once 'tag.php';
				Tag::RemoveFromPost($db, $this->Post, $deltags);
			}
			if ($addtags) {
				require_once 'tag.php';
				Tag::AddToPost($db, $this->Post, 'guides', $addtags);
			}

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating guide', $mse);
		}
	}

	private function SaveChapters(mysqli $db): void {
		$insert = $db->prepare('insert into chapter (guide, number, title, html, markdown) values (?, ?, ?, ?, ?)');
		$number = 0;
		$insert->bind_param('sisss', $this->ID, $number, $title, $html, $markdown);
		foreach ($this->Chapters as $chapter) {
			$number++;
			$title = $chapter->Title;
			$html = FormatText::Markdown($chapter->Markdown);
			$markdown = $chapter->Markdown;
			$insert->execute();
		}
		$insert->close();
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
			$select = $db->prepare('select p.title from guide as g left join post as p on p.id=g.post where g.id=?');
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
			throw DetailedException::FromMysqliException('error checking guide id', $mse);
		}
	}

	public static function Publish(mysqli $db, string $id): ?self {
		$guide = self::FromID($db, $id);
		if ($guide)
			try {
				$db->begin_transaction();

				$update = $db->prepare('update post set published=true, instant=now() where id=? and published=false limit 1');
				$update->bind_param('i', $guide->Post);
				$update->execute();

				$update = $db->prepare('update guide as g join post as p on p.id=g.post set g.updated=p.instant where g.id=?');  // can't use limit here
				$update->bind_param('s', $guide->ID);
				$update->execute();

				$db->commit();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error publishing guide', $mse);
			}
		return $guide;
	}

	public static function Delete(mysqli $db, string $id): ?self {
		$guide = self::FromID($db, $id);
		if ($guide) {
			if ($guide->Published)
				throw new DetailedException('unable to delete published guide.');
			try {
				$db->begin_transaction();

				$delete = $db->prepare('delete from post_tag where post=?');
				$delete->bind_param('i', $guide->Post);
				$delete->execute();

				$delete = $db->prepare('delete from chapter where guide=?');
				$delete->bind_param('s', $guide->ID);
				$delete->execute();

				$delete = $db->prepare('delete from guide where id=? limit 1');
				$delete->bind_param('s', $guide->ID);
				$delete->execute();

				$delete = $db->prepare('delete from post where id=? limit 1');
				$delete->bind_param('i', $guide->Post);
				$delete->execute();

				$db->commit();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error deleting guide', $mse);
			}
		}
		return $guide;
	}
}

class EditChapter {
	public string $Title;
	public string $Markdown;

	public function __construct(string $title, string $markdown) {
		$this->Title = $title;
		$this->Markdown = $markdown;
	}
}
