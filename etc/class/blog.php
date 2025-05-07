<?php
require_once 'environment.php';
require_once 'formatDate.php';

class IndexBlog {
	private const ListLimit = 24;

	public string $ID;
	public ?TimeTagData $Instant = null;
	public string $Title;
	public string $Preview;
	public array $Tags = [];
	public int $CommentCount;

	private function __construct(CurrentUser $user, string $id, ?int $instant, string $title, string $preview, string $tags, int $commentCount) {
		$this->ID = $id;
		if ($instant)
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->Preview = $preview;
		$this->Tags = explode(',', $tags);
		$this->CommentCount = $commentCount;
	}

	/**
	 * Get the next group of blog entries to list.
	 * @param $db Database connection
	 * @param $user Signed-in user
	 * @param $tagName Only list entries with this tag, or list all entries if blank
	 * @param $skip Number of entries to skip (zero unless getting more)
	 * @return BlogList Next group of entries
	 */
	public static function List(mysqli $db, CurrentUser $user, string $tagName = '', int $skip = 0): BlogList {
		$limit = self::ListLimit + 1;
		$select = $tagName
			? 'select b.id, unix_timestamp(p.instant), p.title, p.preview, ifnull(group_concat(distinct t.tag),\'\'), count(distinct c.id) from blog as b left join post as p on p.id=b.post left join post_tag t on t.post=p.id left join comment as c on c.post=p.id join post_tag pt on pt.post=p.id and pt.tag=? where p.published=true group by b.id order by p.instant desc, p.id desc limit ? offset ?'
			: 'select b.id, unix_timestamp(p.instant), p.title, p.preview, ifnull(group_concat(distinct t.tag),\'\'), count(distinct c.id) from blog as b left join post as p on p.id=b.post left join post_tag t on t.post=p.id left join comment as c on c.post=p.id where p.published=true group by b.id order by p.instant desc, p.id desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			if ($tagName)
				$select->bind_param('sii', $tagName, $limit, $skip);
			else
				$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $title, $preview, $tags, $commentCount);
			$result = new BlogList();
			while ($select->fetch())
				if (count($result->Entries) < self::ListLimit)
					$result->Entries[] = new self($user, $id, $instant, $title, $preview, $tags, $commentCount);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up blog entries', $mse);
		}
	}

	/**
	 * Get the full list of draft entries by the current user.
	 * @param $db Database connection
	 * @param $user Signed-in user
	 * @return TitledLink[] Draft blog entries
	 */
	public static function ListDrafts(mysqli $db, CurrentUser $user): array {
		$drafts = [];
		try {
			$select = $db->prepare('select b.id, p.title from blog as b left join post as p on p.id=b.post where p.published=false and p.author=? order by p.instant desc');
			$select->bind_param('i', $user->ID);
			$select->execute();
			$select->bind_result($id, $title);
			while ($select->fetch())
				$drafts[] = new TitledLink($title, $id);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up draft blog entries', $mse);
		}
		return $drafts;
	}
}

/**
 * Partial list of blog entries
 */
class BlogList {
	/**
	 * @var IndexBlog[] Group of entries loaded
	 */
	public array $Entries = [];
	/**
	 * Whether there are more entries to load
	 */
	public bool $HasMore = false;
}

class Blog {
	public string $ID;
	public bool $Published;
	public int $Post;
	public ?TimeTagData $Instant = null;
	public string $Title;
	public string $HTML;
	public array $Tags = [];

	public function __construct(CurrentUser $user, string $id, bool $published, int $post, int $instant, string $title, string $html, string $tags) {
		$this->ID = $id;
		$this->Published = $published;
		$this->Post = $post;
		if ($instant)
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->HTML = $html;
		$this->Tags = explode(',', $tags);
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['name']) || !$_GET['name'])
			return null;
		try {
			$select = $db->prepare('select b.id, p.published, b.post, unix_timestamp(p.instant), p.title, b.html, ifnull(group_concat(pt.tag),\'\') from blog as b left join post as p on p.id=b.post left join post_tag as pt on pt.post=p.id where b.id=? and (p.published=true or p.author=?) group by b.id limit 1');
			$select->bind_param('si', $_GET['name'], $user->ID);
			$select->execute();
			$select->bind_result($id, $published, $post, $instant, $title, $html, $tags);
			if ($select->fetch())
				return new self($user, $id, $published, $post, $instant, $title, $html, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up blog entry', $mse);
		}
		return null;
	}
}

class EditBlog {
	public string $ID;
	public string $Title;
	public string $Markdown;
	public string $Tags;

	public function __construct(string $id, string $title, string $markdown, string $tags) {
		$this->ID = $id;
		$this->Title = $title;
		$this->Markdown = $markdown;
		$this->Tags = $tags;
	}

	public static function FromID(mysqli $db, string $id): ?self {
		try {
			$select = $db->prepare('select b.id, p.title, coalesce(nullif(b.markdown, \'\'), b.html), ifnull(group_concat(pt.tag),\'\') from blog as b left join post as p on p.id=b.post left join post_tag as pt on pt.post=p.id where b.id=? group by b.id limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $title, $markdown, $tags);
			if ($select->fetch())
				return new self($id, $title, $markdown, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up blog entry', $mse);
		}
		return null;
	}

	private static function FromPostID(mysqli $db, int $post): ?self {
		try {
			$select = $db->prepare('select b.id, p.title, coalesce(nullif(b.markdown, \'\'), b.html), ifnull(group_concat(pt.tag),\'\') from blog as b left join post as p on p.id=b.post left join post_tag as pt on pt.post=p.id where b.post=? group by b.id limit 1');
			$select->bind_param('i', $post);
			$select->execute();
			$select->bind_result($id, $title, $markdown, $tags);
			if ($select->fetch())
				return new self($id, $title, $markdown, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up blog entry', $mse);
		}
		return null;
	}

	public static function FromPOST(): self {
		if (!isset($_POST['id'], $_POST['title'], $_POST['markdown']))
			throw new DetailedException('id, title, and markdown are required');
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($markdown = trim($_POST['markdown'])))
			throw new DetailedException('markdown cannot be blank');
		$deltags = isset($_POST['deltags']) ? trim($_POST['deltags']) : '';
		$addtags = isset($_POST['addtags']) ? trim($_POST['addtags']) : '';
		return new self($id, $title, $markdown, "-$deltags+$addtags");
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
			$select = $db->prepare('select p.title from blog as b left join post as p on p.id=b.post where b.id=?');
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
			throw DetailedException::FromMysqliException('error checking blog id', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Markdown);
			$preview = FormatText::Preview($html);

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore, published) values (now(), ?, \'bln\', concat(\'/bln/\', ?), 1, ?, ?, false)');
			$insert->bind_param('sssi', $this->Title, $this->ID, $preview->Text, $preview->HasMore);
			$insert->execute();
			$post = $db->insert_id;

			$insert = $db->prepare('insert into blog (id, post, html, markdown) values (?, ?, ?, ?)');
			$insert->bind_param('siss', $this->ID, $post, $html, $this->Markdown);
			$insert->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::AddToPost($db, $post, 'bln', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new art', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Markdown);
			$update = $db->prepare('update blog set id=?, html=?, markdown=? where id=? limit 1');
			$update->bind_param('ssss', $this->ID, $html, $this->Markdown, $oldID);
			$update->execute();

			$select = $db->prepare('select post from blog where id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($post);
			while ($select->fetch());

			$preview = FormatText::Preview($html);
			$update = $db->prepare('update post set title=?, url=concat(\'/bln/\', ?), preview=?, hasmore=? where id=?');
			$update->bind_param('sssii', $this->Title, $this->ID, $preview->Text, $preview->HasMore, $post);
			$update->execute();

			$tags = explode('+', trim($this->Tags, '-'));
			require_once 'tag.php';
			Tag::RemoveFromPost($db, $post, $tags[0]);
			Tag::AddToPost($db, $post, 'bln', $tags[1]);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating blog entry', $mse);
		}
	}

	public static function Publish(mysqli $db, int $post): ?self {
		$entry = self::FromPostID($db, $post);
		if ($entry)
			try {
				$update = $db->prepare('update post set published=true, instant=now() where id=? and published=false limit 1');
				$update->bind_param('i', $post);
				$update->execute();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error publishing blog entry', $mse);
			}
		return $entry;
	}

	public static function Delete(mysqli $db, string $id): ?self {
		$entry = self::FromID($db, $id);
		if ($entry) {
			if ($entry->Published)
				throw new DetailedException('unable to delete published blog entry.');
			try {
				$db->begin_transaction();

				$delete = $db->prepare('delete from post_tag where post=?');
				$delete->bind_param('i', $entry->Post);
				$delete->execute();

				$delete = $db->prepare('delete from blog where id=? limit 1');
				$delete->bind_param('s', $entry->ID);
				$delete->execute();

				$delete = $db->prepare('delete from post where id=? limit 1');
				$delete->bind_param('i', $entry->Post);
				$delete->execute();

				$db->commit();
			} catch (mysqli_sql_exception $mse) {
				throw DetailedException::FromMysqliException('error deleting blog entry', $mse);
			}
		}
		return $entry;
	}
}
