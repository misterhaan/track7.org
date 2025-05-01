<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class UserSettings extends Page {
	public function __construct() {
		parent::__construct('settings');
	}

	protected static function MainContent(): void {
?>
		<h1>settings</h1>
		<?php
		if (!self::IsUserLoggedIn()) {
		?>
			<p>
				to change your settings, we need to know who you are. sign in and tweak
				track7 just how you like it.
			</p>
		<?php
		} else {
		?>
			<div class=tabbed></div>
<?php
		}
	}
}
new UserSettings();
