<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

abstract class TransitionPage extends Page {
	protected static Subsite $subsite;
	protected static bool $requireAllUsers = false;

	public function __construct() {
		parent::__construct(self::$subsite->name . ' migration');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$subsite->name; ?> migration</h1>
		<?php
		self::RequireDatabase();
		self::CheckUserTable();
	}

	private static function CheckUserTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'user\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>user</code> table exists.</p>
		<?php
			if (self::$requireAllUsers)
				self::CheckUserRows();
			else
				self::CheckUserRow();
		} else
			self::UserSetupLink();
	}

	private static function CheckUserRows(): void {
		// TODO:  check for all users to be migrated
	}

	private static function CheckUserRow(): void {
		$exists = self::$db->query('select 1 from user where id=1 limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>author exists in new <code>user</code> table.</p>
		<?php
			self::CheckSubsiteTable();
		} else
			self::UserSetupLink();
	}

	private static function CheckSubsiteTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'subsite\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>subsite</code> table exists.</p>
		<?php
			self::CheckSubsiteRow();
		} else
			self::CreateSubsiteTable();
	}

	private static function CheckSubsiteRow(): void {
		$exists = self::$db->query('select 1 from subsite where id=\'' . self::$subsite->id . '\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p><?= self::$subsite->name; ?> exists in new <code>subsite</code> table.</p>
		<?php
			self::CheckPostTable();
		} else
			self::CreateSubsiteRow();
	}

	private static function CheckPostTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'post\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>post</code> table exists.</p>
		<?php
			static::CheckPostRows();
		} else
			self::CreatePostTable();
	}

	protected abstract static function CheckPostRows(): void;

	protected static function CheckTagTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'tag\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>tag</code> table exists.</p>
		<?php
			static::CheckTagRows();
		} else
			self::CreateTagTable();
	}

	protected abstract static function CheckTagRows(): void;

	protected static function CheckPostTagTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'post_tag\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>post_tag</code> table exists.</p>
		<?php
			static::CheckPostTagRows();
		} else
			self::CreatePostTagTable();
	}

	protected abstract static function CheckPostTagRows(): void;

	private static function UserSetupLink(): void {
		?>
		<p><a href=users.php>user migration</a> is not far enough along to start <?= self::$subsite->name; ?> migration.</p>
	<?php
	}

	private static function CreateSubsiteTable(): void {
		$file = file_get_contents('../../etc/db/tables/subsite.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreateSubsiteRow(): void {
		$insert = self::$db->prepare('insert into subsite (id, name, calltoaction, verb) values (?, ?, ?, ?)');
		$insert->bind_param('ssss', self::$subsite->id, self::$subsite->name, self::$subsite->calltoaction, self::$subsite->verb);
		$insert->execute();
		$insert->close();
	?>
		<p>created photo album row in new <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreatePostTable(): void {
		$file = file_get_contents('../../etc/db/tables/post.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreateTagTable(): void {
		$file = file_get_contents('../../etc/db/tables/tag.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreatePostTagTable(): void {
		$file = file_get_contents('../../etc/db/tables/post_tag.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>post_tag</code> table. refresh the page to take the next step.</p>
<?php
	}
}

class Subsite {
	public string $id;
	public string $name;
	public string $calltoaction;
	public string $verb;

	public function __construct(string $id, string $name, string $calltoaction, string $verb) {
		$this->id = $id;
		$this->name = $name;
		$this->calltoaction = $calltoaction;
		$this->verb = $verb;
	}
}
