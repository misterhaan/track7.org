<?php
require_once 'environment.php';
require_once 'formatDate.php';

class IndexScript {
	private const ListLimit = 24;

	public string $ID;
	public TimeTagData $Instant;
	public string $Title;
	public string $Type;
	public string $Description;

	public function __construct(CurrentUser $user, string $id, int $instant, string $title, string $type, string $description) {
		$this->ID = $id;
		if ($instant)
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->Type = $type;
		$this->Description = $description;
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip = 0): ScriptList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select s.id, unix_timestamp(p.instant), p.title, s.type, s.description from script as s left join post as p on p.id=s.post order by p.instant desc limit ? offset ?');
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $title, $type, $description);
			$result = new ScriptList();
			while ($select->fetch())
				$result->Scripts[] = new self($user, $id, $instant, $title, $type, $description);
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up web scripts', $mse);
		}
	}
}

/**
 * Partial list of web scripts
 */
class ScriptList {
	/**
	 * @var IndexScript[] Group of web scripts loaded
	 */
	public array $Scripts = [];
	/**
	 * Whether there are more web scripts to load
	 */
	public bool $HasMore = false;
}

class Script extends IndexScript {
	public int $Post;
	public string $Download;
	public string $GitHub;
	public string $Wiki;
	public string $Instructions;

	private function __construct(CurrentUser $user, string $id, int $post, int $instant, string $title, string $type, string $download, string $github, string $wiki, string $description, string $instructions) {
		parent::__construct($user, $id, $instant, $title, $type, $description);
		$this->Post = $post;
		$this->Download = $download ? $download : "files/$id.zip";
		$this->GitHub = $github;
		$this->Wiki = $wiki;
		$this->Instructions = $instructions;
	}

	public static function FromQueryString(mysqli $db, CurrentUser $user): ?self {
		if (!isset($_GET['url']) || !$_GET['url'])
			return null;
		try {
			$select = $db->prepare('select s.id, s.post, unix_timestamp(p.instant), p.title, s.type, s.download, s.github, s.wiki, s.description, s.instructions from script as s left join post as p on p.id=s.post where s.id=? limit 1');
			$select->bind_param('s', $_GET['url']);
			$select->execute();
			$select->bind_result($id, $post, $instant, $title, $type, $download, $github, $wiki, $description, $instructions);
			if ($select->fetch())
				return new self($user, $id, $post, $instant, $title, $type, $download, $github, $wiki, $description, $instructions);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up script', $mse);
		}
		return null;
	}
}

class EditScript {
	public string $ID;
	public string $Title;
	public string $Type;
	public string $Description;
	public string $Instructions;
	public string $Download;
	public string $GitHub;
	public string $Wiki;
	public int $Instant;

	private function __construct(string $id, string $title, string $type, string $description, string $instructions, string $download, string $github, string $wiki, int $instant) {
		$this->ID = $id;
		$this->Title = $title;
		$this->Type = $type;
		$this->Description = $description;
		$this->Instructions = $instructions;
		$this->Download = $download;
		$this->GitHub = $github;
		$this->Wiki = $wiki;
		$this->Instant = $instant;
	}

	public static function FromID(mysqli $db, string $id): ?self {
		try {
			$select = $db->prepare('select s.id, p.title, s.type, s.mddescription, s.mdinstructions, s.download, s.github, s.wiki, unix_timestamp(p.instant) from script as s left join post as p on p.id=s.post where s.id=? limit 1');
			$select->bind_param('s', $id);
			$select->execute();
			$select->bind_result($id, $title, $type, $description, $instructions, $download, $github, $wiki, $instant);
			if ($select->fetch())
				return new self($id, $title, $type, $description, $instructions, $download, $github, $wiki, $instant);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up application', $mse);
		}
		return null;
	}

	public static function FromPOST(CurrentUser $user): self {
		if (!isset($_POST['id'], $_POST['title'], $_POST['type'], $_POST['description']))
			throw new DetailedException('id, title, type, and description are required');
		if (!($id = trim($_POST['id'])))
			throw new DetailedException('id cannot be blank');
		if (!($title = trim($_POST['title'])))
			throw new DetailedException('title cannot be blank');
		if (!($type = trim($_POST['type'])))
			throw new DetailedException('type cannot be blank');  // this may still fail on insert / update but we'll let the database handle that.
		if (!($description = trim($_POST['description'])))
			throw new DetailedException('description cannot be blank');
		$instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
		$download = isset($_POST['download']) ? trim($_POST['download']) : '';
		$github = isset($_POST['github']) ? trim($_POST['github']) : '';
		$wiki = isset($_POST['wiki']) ? trim($_POST['wiki']) : '';
		if (isset($_POST['instant']) && $instant = trim($_POST['instant'])) {
			require_once 'formatDate.php';
			$instant = FormatDate::LocalToTimestamp($instant, $user);
		}
		if (!$instant)
			$instant = time();
		return new self($id, $title, $type, $description, $instructions, $download, $github, $wiki, $instant);
	}

	public static function IdAvailable(mysqli $db, string $oldID, string $newID): ValidationResult {
		if ($oldID == $newID)
			return new ValidationResult('valid');
		try {
			$select = $db->prepare('select p.title from script as s left join post as p on p.id=s.post where s.id=? limit 1');
			$select->bind_param('s', $newID);
			$select->execute();
			$select->bind_result($title);
			if ($select->fetch())
				return new ValidationResult('invalid', "“{$newID}” already in use by $title.");
			return new ValidationResult('valid');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking web script id', $mse);
		}
	}

	public function SaveNew(mysqli $db): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$description = FormatText::Markdown($this->Description);
			$instructions = FormatText::Markdown($this->Instructions);
			$preview = FormatText::Preview($description);

			$insert = $db->prepare('insert into post (instant, title, subsite, url, author, preview, hasmore) values (from_unixtime(?), ?, \'code\', concat(\'/code/web/\', ?), 1, ?, true)');
			$insert->bind_param('isss', $this->Instant, $this->Title, $this->ID, $preview->Text);
			$insert->execute();
			$post = $db->insert_id;

			$insert = $db->prepare('insert into script (id, post, type, download, github, wiki, mddescription, description, mdinstructions, instructions) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$insert->bind_param('sissssssss', $this->ID, $post, $this->Type, $this->Download, $this->GitHub, $this->Wiki, $this->Description, $description, $this->Instructions, $instructions);
			$insert->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new web script', $mse);
		}
	}

	public function Update(mysqli $db, string $oldID): void {
		try {
			$db->begin_transaction();

			require_once 'formatText.php';
			$description = FormatText::Markdown($this->Description);
			$instructions = FormatText::Markdown($this->Instructions);
			$preview = FormatText::Preview($description);

			$update = $db->prepare('update script set id=?, type=?, download=?, github=?, wiki=?, mddescription=?, description=?, mdinstructions=?, instructions=? where id=? limit 1');
			$update->bind_param('ssssssssss', $this->ID, $this->Type, $this->Download, $this->GitHub, $this->Wiki, $this->Description, $description, $this->Instructions, $instructions, $oldID);
			$update->execute();

			$select = $db->prepare('select post from script where id=? limit 1');
			$select->bind_param('s', $this->ID);
			$select->execute();
			$select->bind_result($post);
			while ($select->fetch());

			$update = $db->prepare('update post set title=?, url=concat(\'/code/vs/\', ?), instant=from_unixtime(?), preview=?, hasmore=true where id=?');
			$update->bind_param('ssisi', $this->Title, $this->ID, $this->Instant, $preview->Text, $post);
			$update->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating web script', $mse);
		}
	}
}
