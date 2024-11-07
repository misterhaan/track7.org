<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class GuestbookIndex extends Page {
	public function __construct() {
		parent::__construct('hosted guestbooks');
	}

	protected static function MainContent(): void {
?>
		<h1>hosted guestbooks</h1>
		<?php
		$db = self::RequireDatabase();
		try {
			$select = $db->prepare('select name from track7_t7data.gbbooks');
			$select->execute();
			$select->bind_result($book);
			$any = false;
		?>
			<ul>
				<?php
				while ($select->fetch()) {
					$any = true;
				?>
					<li><a href="view.php?book=<?= $book->name; ?>"><?= $book->name; ?></a></li>
				<?php
				}
				?>
			</ul>
			<?php
			if (!$any) {
			?>
				<p>no hosted guestbooks found.</p>
<?php
			}
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up hosted guestbooks', $mse);
		}
	}
}
new GuestbookIndex();
