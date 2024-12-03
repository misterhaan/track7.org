<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class LegoEdit extends Page {
	private static string $pageTitle;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 lego model not found', '<p>sorry, we don’t have a lego model by that name.  try picking one from <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>the gallery</a>.</p>');
		self::$pageTitle = (isset($_GET['id']) && trim($_GET['id']) ? 'edit' : 'add') . ' lego model';
		parent::__construct(self::$pageTitle);
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$pageTitle; ?></h1>

		<div id=editlego></div>
<?php
	}
}
new LegoEdit();
