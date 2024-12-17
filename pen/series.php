<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'story.php';

class SeriesIndex extends Page {
	private static ?Series $series;

	public function __construct() {
		self::$series = Series::FromQueryString(self::RequireDatabase());
		if (!self::$series)
			self::NotFound('404 series not found', '<p>sorry, we don’t seem to have a series by that name. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all stories</a>.</p>');
		parent::__construct(self::$series->Title . ' - stories');
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$series->Title); ?></h1>
		<?= self::$series->Description; ?>

		<section id=serieslist></section>
<?php
	}
}
new SeriesIndex();
