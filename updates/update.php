<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'update.php';

class SingleUpdate extends Page {
	private static ?Update $update;

	public function __construct() {
		self::$update = Update::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$update)
			self::NotFound('404 site update not found', '<p>sorry, we don’t seem to have a site update with that id. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all site updates</a>.</p>');
		parent::__construct('track7 update');
	}

	protected static function MainContent(): void {
?>
		<h1>track7 update</h1>
		<p class=guidemeta><time class=posted title="posted <?= self::$update->Instant->Tooltip; ?>" datetime="<?= self::$update->Instant->DateTime; ?>"><?= self::$update->Instant->Display; ?></time></p>
<?php
		echo self::$update->HTML;
		self::ShowComments(self::$update->ID);
	}
}
new SingleUpdate();
