<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class PhotoTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('photo album');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckPostImages();
	}

	private static function CheckPostImages(): void {
		$fullSize = self::$db->query('select 1 from post where subsite=\'album\' and preview like \'%.jpeg"></p>\' or preview like \'%img class=photo src%\' limit 1');
		if ($fullSize->fetch_column())
			self::UpdatePostImages();
		else {
?>
			<p>all photo post previews use thumbnail image.</p>
		<?php
			self::Done();
		}
	}

	private static function UpdatePostImages(): void {
		self::$db->real_query('update post set preview=replace(replace(preview,\'.jpeg"></p>\',\'.jpg"></p>\'),\'img class=photo src\',\'img src\') where subsite=\'album\' and preview like \'%.jpeg"></p>\' or preview like \'%img class=photo src%\'');
		?>
		<p>updated photo post previews to use thumbnail images. refresh the page to take the next step.</p>
<?php
	}
}
new PhotoTransition();
