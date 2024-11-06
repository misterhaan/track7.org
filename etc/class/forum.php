<?php
require_once 'environment.php';
require_once 'formatDate.php';

class IndexDiscussion extends Discussion {
	private const ListLimit = 24;

	public TimeTagData $StartInstant;
	public string $StarterName;
	public string $StarterContact;
	public int $ReplyCount;
	public ?TimeTagData $LatestInstant = null;
	public ?string $LatestName = null;
	public ?string $LatestContact = null;

	private function __construct(CurrentUser $user, int $id, string $title, string $tags, int $startinstant, string $startername, string $startercontact, int $replycount, int $latestinstant, string $latestname, string $latestcontact) {
		parent::__construct($id, $title, $tags);
		$this->StartInstant = new TimeTagData($user, 'ago', $startinstant, FormatDate::Long);
		$this->StarterName = $startername;
		$this->StarterContact = $startercontact;
		if ($this->ReplyCount = $replycount) {  // if there haven't been any replies then latest is the same as start and it isn't shown
			$this->LatestInstant = new TimeTagData($user, 'ago', $latestinstant, FormatDate::Long);
			$this->LatestName = $latestname;
			$this->LatestContact = $latestcontact;
		}
	}

	/**
	 * Get the next group of forum discussions to list.
	 * @param $db Database connection
	 * @param $user Signed-in user
	 * @param $tagName Only list discussions with this tag, or list all discussions if blank
	 * @param $skip Number of discussions to skip (zero unless getting more)
	 * @return DiscussionList Next group of discussions
	 */
	public static function List(mysqli $db, CurrentUser $user, string $tagName = '', int $skip = 0): DiscussionList {
		$limit = self::ListLimit + 1;
		$select = 'select d.id, d.title, d.tags, unix_timestamp(d.startinstant), d.startername, d.startercontact, d.replies, unix_timestamp(d.latestinstant), d.latestname, d.latestcontact from discussion as d';
		if ($tagName)
			$select .= ' join post_tag as pt on pt.post=d.id and pt.tag=?';
		$select .= ' order by d.latestinstant desc limit ? offset ?';
		try {
			$select = $db->prepare($select);
			if ($tagName)
				$select->bind_param('sii', $tagName, $limit, $skip);
			else
				$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $title, $tags, $startinstant, $startername, $startercontact, $replies, $latestinstant, $latestname, $latestcontact);
			$result = new DiscussionList();
			while ($select->fetch())
				if (count($result->Discussions) < self::ListLimit)
					$result->Discussions[] = new self($user, $id, $title, $tags, $startinstant, $startername, $startercontact, $replies, $latestinstant, $latestname, $latestcontact);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up forum discussions', $mse);
		}
	}
}

/**
 * Partial list of forum discussions
 */
class DiscussionList {
	/**
	 * @var IndexDiscussion[] Group of discussions loaded
	 */
	public array $Discussions = [];
	/**
	 * Whether there are more discussions to load
	 */
	public bool $HasMore = false;
}

class Discussion {
	public int $ID;
	public string $Title;
	public array $Tags = [];

	protected function __construct(int $id, string $title, string $tags) {
		$this->ID = $id;
		$this->Title = $title;
		$this->Tags = explode(',', $tags);
	}

	public static function FromQueryString(mysqli $db): ?self {
		if (!isset($_GET['id']) || !$_GET['id'])
			return null;
		try {
			$select = $db->prepare('select p.id, p.title, group_concat(pt.tag) from post as p left join post_tag as pt on pt.post=p.id where p.id=? and p.subsite=\'forum\' group by p.id limit 1');
			$select->bind_param('i', $_GET['id']);
			$select->execute();
			$select->bind_result($id, $title, $tags);
			if ($select->fetch())
				return new self($id, $title, $tags);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up discussion', $mse);
		}
		return null;
	}

	public static function FromPOST(): self {
		if (!isset($_POST['title'], $_POST['tags'], $_POST['message']))
			throw new DetailedException('title, tags, and message are required');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($tags = trim($_POST['tags'])))
			throw new DetailedException('tags cannot be blank');
		if (!trim($_POST['message']))
			throw new DetailedException('message cannot be blank');
		return new self(-1, $title, $tags);
	}

	public function Start(mysqli $db, CurrentUser $user, ?string $name, ?string $contact, string $message): void {
		try {
			$db->begin_transaction();

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (now(), ?, \'forum\', \'/forum/new\', 1, \'\', true)');
			$insert->bind_param('s', $this->Title);
			$insert->execute();
			$this->ID = $insert->insert_id;

			$update = $db->prepare('update post set url=concat(\'/forum/\', ?) where id=? limit 1');
			$update->bind_param('ii', $this->ID, $this->ID);
			$update->execute();

			require_once 'tag.php';
			Tag::AddToPost($db, $this->ID, 'forum', implode(',', $this->Tags));

			require_once 'comment.php';
			Comment::Create($db, $user, $this->ID, $message, $name, $contact);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error starting discussion', $mse);
		}
	}
}
