<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class LegoIndex extends Page {
	private const Subsite = 'lego';

	public function __construct() {
		self::$bodytype = 'gallery';
		parent::__construct('original lego models');
	}

	protected static function MainContent(): void {
?>
		<h1>original lego models</h1>
		<?php
		if (self::HasAdminSecurity()) {
		?>
			<nav class=actions><a href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add lego model</a></nav>
		<?php
		}
		?>
		<p>
			these lego models are <a href="/user/misterhaan/">my</a> own original
			creations. each has step-by-step instructions and <a href="http://www.ldraw.org/">ldraw</a>
			model data file available for download.
		</p>
		<section id=legomodels></section>
<?php
	}
}
new LegoIndex();
