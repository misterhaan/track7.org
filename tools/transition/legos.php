<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class LegoTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('lego models');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckPostImages();
	}

	private static function CheckPostImages(): void {
		$fullSize = self::$db->query('select 1 from post where subsite=\'lego\' and preview not like \'%-thumb.png%\' or preview like \'%img class=lego src%\' limit 1');
		if ($fullSize->fetch_column())
			self::UpdatePostImages();
		else {
?>
			<p>all lego post previews use thumbnail image.</p>
		<?php
			self::Done();
		}
	}

	private static function UpdatePostImages(): void {
		self::$db->real_query('update post set preview=replace(preview,\'.png\',\'-thumb.png\') where subsite=\'lego\' and preview not like \'%-thumb.%\'');
		self::$db->real_query('update post set preview=replace(preview,\'img class=lego src\',\'img src\') where subsite=\'lego\' and preview like \'%img class=lego src%\'');
		?>
		<p>updated lego post previews to use thumbnail images. refresh the page to take the next step.</p>
<?php
	}
}
new LegoTransition();
