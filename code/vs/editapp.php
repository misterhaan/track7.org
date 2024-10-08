<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class ApplicationEdit extends Page {
	private static string $id;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 application not found', '<p>sorry, we don’t have an application by that name.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all applications</a>.</p>');
		self::$id = isset($_GET['id']) ? trim($_GET['id']) : '';
		parent::__construct((self::$id ? 'edit' : 'add') . ' application - software');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$id ? 'edit' : 'add'; ?> application</h1>
		<div id=editapp></div>
<?php
	}
}
new ApplicationEdit();
