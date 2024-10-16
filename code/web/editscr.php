<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'script.php';

class ScriptEdit extends Page {
	private static string $id;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 web script not found', '<p>sorry, we don’t have a web script by that name.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all web scripts</a>.</p>');
		self::$id = isset($_GET['id']) ? trim($_GET['id']) : '';
		parent::__construct((self::$id ? 'edit' : 'add') . ' web script - software');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$id ? 'edit' : 'add'; ?> web script</h1>
		<div id=editscr></div>
<?php
	}
}
new ScriptEdit();
