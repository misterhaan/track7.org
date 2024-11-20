<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class GuideEdit extends Page {
	private static string $id;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 guide not found', '<p>sorry, we don’t have a guide by that name.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all guides</a>.</p>');
		self::$id = isset($_GET['id']) ? trim($_GET['id']) : '';
		parent::__construct((self::$id ? 'edit' : 'add') . ' guide');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$id ? 'edit' : 'add'; ?> guide</h1>

		<div id=editguide></div>
<?php
	}
}
new GuideEdit();
