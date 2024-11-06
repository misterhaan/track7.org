<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class SignGuestbook extends Page {
	public function __construct() {
		parent::__construct('sign guestbook');
	}

	protected static function MainContent(): void {
		$book = isset($_GET['book']) ? trim($_GET['book']) : '';
?>
		<h1>sign guestbook</h1>
		<p>
			sorry, but track7 no longer provides hosted guestbook services. you may
			<a href="view.php?book=<?= htmlspecialchars($book); ?>">view this guestbook</a>,
			but may not sign it.
		</p>
<?php
	}
}
new SignGuestbook();
