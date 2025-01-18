<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

abstract class TransitionPage extends Page {
	private static string $thing;

	public function __construct(string $thing) {
		self::$thing = $thing;
		parent::__construct("$thing migration");
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$thing; ?> migration</h1>
		<?php
		self::RequireDatabase();
	}

	protected static function CheckSubsiteTable(): void {
		if (self::CheckTableExists('subsite')) {
		?>
			<p>new <code>subsite</code> table exists.</p>
		<?php
			static::SubsiteTableExists();
		} else
			self::CreateTable('subsite');
	}

	protected static function SubsiteTableExists(): void {
		throw new DetailedException('SubsiteTableExists() not implemented');
	}

	protected static function CheckTableExists(string $name): bool {
		$exists = self::$db->prepare('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=? limit 1');
		$exists->bind_param('s', $name);
		$exists->execute();
		return $exists->fetch() ? true : false;
	}

	protected static function CheckViewExists(string $name): bool {
		$exists = self::$db->prepare('select 1 from information_schema.views where table_schema=\'track7\' and table_name=? limit 1');
		$exists->bind_param('s', $name);
		$exists->execute();
		return $exists->fetch() ? true : false;
	}

	protected static function CheckTriggersExist(string $tableName): bool {
		$exists = self::$db->prepare('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=? limit 1');
		$exists->bind_param('s', $tableName);
		$exists->execute();
		return $exists->fetch() ? true : false;
	}

	protected static function CreateTable(string $name): void {
		$file = file_get_contents("../../etc/db/tables/$name.sql");
		self::$db->real_query($file);
		?>
		<p>created <code><?= $name; ?></code> table. refresh the page to take the next step.</p>
	<?php
	}

	protected static function CreateView(string $name): void {
		$file = file_get_contents("../../etc/db/views/$name.sql");
		self::$db->real_query($file);
	?>
		<p>created <code><?= $name; ?></code> view. refresh the page to take the next step.</p>
	<?php
	}

	protected static function Done(): void {
	?>
		<p>done migrating <?= self::$thing; ?>, at least for now!</p>
<?php
	}
}
