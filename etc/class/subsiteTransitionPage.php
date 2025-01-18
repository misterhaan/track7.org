<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

abstract class SubsiteTransitionPage extends TransitionPage {
	protected static Subsite $subsite;
	protected static bool $requireAllUsers = false;

	public function __construct() {
		parent::__construct(self::$subsite->name);
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckUserTable();
	}

	private static function CheckUserTable(): void {
		if (self::CheckTableExists('user')) {
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
		throw new DetailedException('can’t check for all users since CheckUserRows() hasn’t been implemented');
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

	protected static function SubsiteTableExists(): void {
		self::CheckSubsiteRow();
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
		if (self::CheckTableExists('post')) {
		?>
			<p>new <code>post</code> table exists.</p>
		<?php
			static::CheckPostPublishedColumn();
		} else
			self::CreateTable('post');
	}

	protected static function CheckPostPublishedColumn(): void {
		$hasNewColumn = self::$db->query('show columns from post like \'published\'');
		if ($hasNewColumn->num_rows) {
		?>
			<p>new <code>post</code> table has published column.</p>
		<?php
			static::CheckPostRows();
		} else
			self::AddPostPublishedColumn();
	}

	protected abstract static function CheckPostRows(): void;

	protected static function CheckTagTable(): void {
		if (self::CheckTableExists('tag')) {
		?>
			<p>new <code>tag</code> table exists.</p>
		<?php
			static::CheckTagRows();
		} else
			self::CreateTable('tag');
	}

	protected abstract static function CheckTagRows(): void;

	protected static function CheckPostTagTable(): void {
		if (self::CheckTableExists('post_tag')) {
		?>
			<p>new <code>post_tag</code> table exists.</p>
		<?php
			static::CheckPostTagRows();
		} else
			self::CreateTable('post_tag');
	}

	protected abstract static function CheckPostTagRows(): void;

	protected static function CheckTagUsageView(): void {
		if (self::CheckViewExists('tagusage')) {
		?>
			<p>new <code>tagusage</code> view exists.</p>
		<?php
			self::CheckCommentTable();
		} else
			self::CreateView('tagusage');
	}

	private static function CheckCommentTable(): void {
		if (self::CheckTableExists('comment')) {
		?>
			<p>new <code>comment</code> table exists.</p>
		<?php
			static::CheckCommentRows();
		} else
			self::CreateTable('comment');
	}

	protected abstract static function CheckCommentRows(): void;

	protected static function CheckVoteTable(): void {
		if (self::CheckTableExists('vote')) {
		?>
			<p>new <code>vote</code> table exists.</p>
		<?php
			self::CheckRatingView();
		} else
			self::CreateTable('vote');
	}

	protected static function CheckRatingView(): void {
		if (self::CheckViewExists('rating')) {
		?>
			<p>new <code>rating</code> view exists.</p>
		<?php
			static::CheckVoteRows();
		} else
			self::CreateView('rating');
	}

	protected static function CheckVoteRows(): void {
		throw new DetailedException('votes not implemented for this transition.');
	}

	private static function UserSetupLink(): void {
		?>
		<p><a href=users.php>user migration</a> is not far enough along to start <?= self::$subsite->name; ?> migration.</p>
	<?php
	}

	private static function CreateSubsiteRow(): void {
		$insert = self::$db->prepare('insert into subsite (id, feature, type, name, calltoaction, verb) values (?, ?, ?, ?, ?, ?)');
		$insert->bind_param('sissss', self::$subsite->id, self::$subsite->feature, self::$subsite->type, self::$subsite->name, self::$subsite->calltoaction, self::$subsite->verb);
		$insert->execute();
		$insert->close();
	?>
		<p>created <?= self::$subsite->name; ?> row in new <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function AddPostPublishedColumn(): void {
		self::$db->real_query('alter table post add published boolean not null default true after id, add key(published)');
		self::$db->real_query('drop view tagusage');
		$file = file_get_contents('../../etc/db/views/tagusage.sql');
		self::$db->real_query($file);
	?>
		<p>added <code>published</code> column to <code>post</code> table. refresh the page to take the next step.</p>
<?php
	}
}

class Subsite {
	public string $id;
	public ?int $feature;
	public string $type;
	public string $name;
	public string $calltoaction;
	public string $verb;

	public function __construct(string $id, ?int $feature, string $type, string $name, string $calltoaction, string $verb) {
		$this->id = $id;
		$this->feature = $feature ? $feature : null;  // 0 isn't allowed
		$this->type = $type;
		$this->name = $name;
		$this->calltoaction = $calltoaction;
		$this->verb = $verb;
	}
}
