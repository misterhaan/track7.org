<?php
require_once 'environment.php';
require_once 'user.php';
require_once 'formatDate.php';

class Application {
	public string $ID;
	public int $Post;
	public string $Title;
	public string $Description;
	public string $GitHub;
	public string $Wiki;

	private function __construct(string $id, int $post, string $title, string $description, string $gitHub, string $wiki) {
		$this->ID = $id;
		$this->Post = $post;
		$this->Title = $title;
		$this->Description = $description;
		$this->GitHub = $gitHub;
		$this->Wiki = $wiki;
	}

	public static function FromQueryString(mysqli $db, string $paramName): ?self {
		if (!isset($_GET[$paramName]) || !$_GET[$paramName])
			return null;
		try {
			$select = $db->prepare('select id, post, name, description, github, wiki from application where id=? limit 1');
			$select->bind_param('s', $_GET[$paramName]);
			$select->execute();
			$select->bind_result($id, $post, $title, $description, $github, $wiki);
			if ($select->fetch())
				return new self($id, $post, $title, $description, $github, $wiki);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up application', $mse);
		}
		return null;
	}
}

class LatestApplication {
	private const ListLimit = 24;

	public string $ID;
	public TimeTagData $Instant;
	public string $Title;
	public ?string $Version;
	public string $Description;
	public ?string $BinURL;
	public ?string $Bin32URL;

	private function __construct(CurrentUser $user, string $id, int $instant, string $title, ?string $version, string $description, ?string $binURL, ?string $bin32URL) {
		$this->ID = $id;
		$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->Version = $version;
		$this->Description = $description;
		$this->BinURL = $binURL;
		$this->Bin32URL = $bin32URL;
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip = 0): ApplicationList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select id, unix_timestamp(instant), name, version, description, binurl, bin32url from latestapplication order by instant desc limit ? offset ?');
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $title, $version, $description, $binURL, $bin32URL);
			$result = new ApplicationList();
			while ($select->fetch())
				if (count($result->Applications) < self::ListLimit)
					$result->Applications[] = new self($user, $id, $instant, $title, $version, $description, $binURL, $bin32URL);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up applications', $mse);
		}
	}
}

/**
 * Partial list of applications
 */
class ApplicationList {
	/**
	 * @var LatestApplication[] Group of applications loaded
	 */
	public array $Applications = [];
	/**
	 * Whether there are more applications to load
	 */
	public bool $HasMore = false;
}

class EditApplication {
	public string $ID;
	public string $Name;
	public string $Markdown;
	public string $GitHub;
	public string $Wiki;

	private function __construct(string $id, string $name, string $markdown, string $github, string $wiki) {
		$this->ID = $id;
		$this->Name = $name;
		$this->Markdown = $markdown;
		$this->GitHub = $github;
		$this->Wiki = $wiki;
	}

	public static function FromID(mysqli $db, string $id): ?self {
		try {
			$select = $db->prepare('select id, name, markdown, github, wiki from application where id=? limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $name, $markdown, $github, $wiki);
			if ($select->fetch())
				return new self($id, $name, $markdown, $github, $wiki);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up application', $mse);
		}
		return null;
	}

	public static function FromPOST(): self {
		if (!isset($_POST['id'], $_POST['name'], $_POST['markdown']))
			throw new DetailedException('id, name, and markdown are required');
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($name = trim($_POST['name'])))
			throw new DetailedException('name cannot be blank');
		if (!($markdown = trim($_POST['markdown'])))
			throw new DetailedException('markdown cannot be blank');
		$github = isset($_POST['github']) ? trim($_POST['github']) : '';
		$wiki = isset($_POST['wiki']) ? trim($_POST['wiki']) : '';
		return new self($id, $name, $markdown, $github, $wiki);
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
			$select = $db->prepare('select name from application where id=? limit 1');
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
			throw DetailedException::FromMysqliException('error checking application id', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Markdown);
			$preview = FormatText::Preview($html);

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (now(), ?, \'code\', concat(\'/code/vs/\', ?), 1, ?, true)');
			$insert->bind_param('sss', $this->Name, $this->ID, $preview->Text);
			$insert->execute();
			$post = $db->insert_id;

			$insert = $db->prepare('insert into application (id, name, post, github, wiki, markdown, description) values (?, ?, ?, ?, ?, ?, ?)');
			$insert->bind_param('ssissss', $this->ID, $this->Name, $post, $this->GitHub, $this->Wiki, $this->Markdown, $html);
			$insert->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new application', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$html = FormatText::Markdown($this->Markdown);
			$update = $db->prepare('update application set id=?, name=?, github=?, wiki=?, markdown=?, description=? where id=? limit 1');
			$update->bind_param('sssssss', $this->ID, $this->Name, $this->GitHub, $this->Wiki, $this->Markdown, $html, $oldID);
			$update->execute();

			$select = $db->prepare('select a.post, la.version from application as a left join latestapplication as la on la.id=a.id where a.id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($post, $version);
			while ($select->fetch());
			$title = $version ? "$this->Name v$version" : $this->Name;

			$preview = FormatText::Preview($html);
			$update = $db->prepare('update post set title=?, url=concat(\'/code/vs/\', ?), preview=?, hasmore=true where id=?');
			$update->bind_param('sssi', $title, $this->ID, $preview->Text, $post);
			$update->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating application', $mse);
		}
	}
}
