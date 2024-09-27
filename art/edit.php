<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class ArtEdit extends Page {
	private static string $name;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 art not found', '<p>sorry, we don’t have any art by that name.  try picking one from <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>the gallery</a>.</p>');
		self::$name = isset($_GET['id']) ? trim($_GET['id']) : '';
		parent::__construct((self::$name ? 'edit' : 'add') . ' art');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$name ? 'edit' : 'add'; ?> art</h1>

		<div id=editart></div>
<?php
	}
}
new ArtEdit();
