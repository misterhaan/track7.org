<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class RecentComments extends Page {
	private static ?User $u;

	public function __construct() {
		self::$u = null;
		if (isset($_GET['username'])) {
			require_once 'user.php';
			self::$u = new User(self::RequireDatabase(), trim($_GET['username']));
		}
		parent::__construct(self::$u ? self::$u->DisplayName . '’s comments' : 'comments');
	}

	protected static function MainContent(): void {
		if (self::$u) {
?>
			<h1>
				<a href="/user/<?= self::$u->Username; ?>/">
					<img class="inline avatar" src="<?= self::$u->Avatar; ?>">
					<?= htmlspecialchars(self::$u->DisplayName); ?></a>’s comments
			</h1>
		<?php
		} else {
		?>
			<h1>comments</h1>
		<?php
		}
		?>
		<div id=recentcomments data-user="<?= self::$u ? self::$u->ID : ''; ?>"></div>
<?php
	}
}
new RecentComments();
