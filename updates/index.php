<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class RecentUpdates extends Page {
	public function __construct() {
		parent::__construct('track7 updates');
	}

	protected static function MainContent(): void {
?>
		<h1>track7 updates</h1>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		?>
		<div id=recentupdates></div>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<div class=floatbgstop>
			<nav class=actions><a class=new href="new.php">add update message</a></nav>
		</div>
<?php
	}
}
new RecentUpdates();
