<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class ActivityTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('activity');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckSubsiteTable();
	}

	protected static function SubsiteTableExists(): void {
		self::CheckFeatureColumn();
	}

	private static function CheckFeatureColumn(): void {
		$exists = self::$db->query('select 1 from information_schema.columns where table_schema=\'track7\' and table_name=\'subsite\' and column_name=\'feature\' limit 1');
		if ($exists->fetch_column()) {
?>
			<p>new <code>subsite</code> table has <code>feature</code> column.</p>
		<?php
			self::CheckTypeColumn();
		} else
			self::AddFeatureColumn();
	}

	private static function CheckTypeColumn(): void {
		$exists = self::$db->query('select 1 from information_schema.columns where table_schema=\'track7\' and table_name=\'subsite\' and column_name=\'type\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>subsite</code> table has <code>type</code> column.</p>
		<?php
			self::CheckActivityView();
		} else
			self::AddTypeColumn();
	}

	private static function CheckActivityView(): void {
		if (self::CheckViewExists('activity')) {
		?>
			<p>new <code>activity</code> view exists.</p>
		<?php
			self::Done();
		} else
			self::CreateView('activity');
	}

	private static function AddFeatureColumn(): void {
		self::$db->begin_transaction();
		self::$db->real_query('alter table subsite add column feature tinyint comment \'order in which this subsite is featured in the main menu\' after id');
		self::$db->real_query('update subsite set feature=case id when \'bln\' then 1 when \'album\' then 2 when \'guides\' then 3 when \'lego\' then 4 when \'art\' then 5 when \'pen\' then 6 when \'code\' then 7 when \'forum\' then 8 end');
		self::$db->commit();
		?>
		<p>added new <code>feature</code> column to <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function AddTypeColumn(): void {
		self::$db->begin_transaction();
		self::$db->real_query('alter table subsite add column type varchar(16) not null comment \'type of content this subsite contains\' after feature');
		self::$db->real_query('update subsite set type=case id when \'album\' then \'photo\' when \'bln\' then \'entry\' when \'forum\' then \'post\' when \'guides\' then \'guide\' when \'pen\' then \'story\' when \'updates\' then \'update\' else id end');
		self::$db->commit();
	?>
		<p>added new <code>type</code> column to <code>subsite</code> table. refresh the page to take the next step.</p>
<?php
	}
}
new ActivityTransition();
