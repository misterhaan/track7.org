<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class SettingsTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('settings');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckSettingsTable();
	}

	private static function CheckSettingsTable(): void {
		if (self::CheckTableExists('settings')) {
?>
			<p>new <code>settings</code> table exists.</p>
			<?php
			self::CheckSettingsRows();
		} else
			self::CreateTable('settings');
	}

	private static function CheckSettingsRows(): void {
		if (self::CheckTableExists('users_settings')) {
			$missing = self::$db->query('select 1 from users_settings as us left join settings as s on s.user=us.id where s.user is null limit 1');
			if ($missing->fetch_column())
				self::CopySettings();
			else {
			?>
				<p>all old settings exist in new <code>settings</code> table.</p>
			<?php
				self::CheckOldSettingsTable();
			}
		} else {
			?>
			<p>old settings table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckOldSettingsTable(): void {
		if (self::CheckTableExists('users_settings')) {
			self::DeleteOldSettingsTable();
		} else {
		?>
			<p>old settings table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopySettings(): void {
		self::$db->real_query('insert into settings (user, timebase, timeoffset, emailnewmessage) select us.id, us.timebase, us.timeoffset, us.unreadmsgs from users_settings as us left join settings as s on s.user=us.id where s.user is null');
		?>
		<p>copied old settings to new <code>settings</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldSettingsTable(): void {
		self::$db->real_query('drop table users_settings');
	?>
		<p>deleted old settings table. refresh the page to take the next step.</p>
<?php
	}
}
new SettingsTransition();
