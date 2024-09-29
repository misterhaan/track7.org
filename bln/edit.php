<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class BlogEntryEdit extends Page {
	private static string $id;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 blog entry not found', '<p>sorry, we don’t have a blog entry by that name.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all blog entries</a>.</p>');
		self::$id = isset($_GET['id']) ? trim($_GET['id']) : '';
		parent::__construct((self::$id ? 'edit' : 'add') . ' entry');
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$id ? 'edit' : 'add'; ?> entry</h1>

		<div id=editentry></div>
<?php
	}
}
new BlogEntryEdit();
