<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class UserTransition extends Page {
	public function __construct() {
		parent::__construct('user migration');
	}

	protected static function MainContent(): void {
?>
		<h1>user migration</h1>
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
			self::CheckUserRows();
		} else
			self::CreateUserTable();
	}

	private static function CheckUserRows(): void {
		$missing = self::$db->query('select 1 from users left join user on user.id=users.id where user.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyUsers();
		else {
		?>
			<p>all old users exist in new <code>user</code> table.</p>
		<?php
			self::Done();
		}
	}

	private static function CreateUserTable(): void {
		$file = file_get_contents('../../etc/db/tables/user.sql');
		self::$db->real_query($file);
		?>
		<p>created <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyUsers(): void {
		self::$db->real_query('insert into user select users.* from users left join user on user.id=users.id where user.id is null');
	?>
		<p>copied users into new <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function Done(): void {
	?>
		<p>done migrating users, at least for now!</p>
<?php
	}
}
new UserTransition();
