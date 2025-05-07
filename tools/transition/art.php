<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class ArtTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('visual art');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckPostImages();
	}

	private static function CheckPostImages(): void {
		$fullSize = self::$db->query('select 1 from post where subsite=\'art\' and preview not like \'%-prev.%\' or preview like \'%img class=art src%\' limit 1');
		if ($fullSize->fetch_column())
			self::UpdatePostImages();
		else {
?>
			<p>all art post previews use thumbnail image.</p>
		<?php
			self::Done();
		}
	}

	private static function UpdatePostImages(): void {
		self::$db->real_query('update post set preview=replace(preview,\'.\',\'-prev.\') where subsite=\'art\' and preview not like \'%-prev.%\'');
		self::$db->real_query('update post set preview=replace(preview,\'img class=art src\',\'img src\') where subsite=\'art\' and preview like \'%img class=art src%\'');
		?>
		<p>updated art post previews to use thumbnail images. refresh the page to take the next step.</p>
<?php
	}
}
new ArtTransition();
