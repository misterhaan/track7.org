<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class NewUpdate extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('post track7 update');
	}

	protected static function MainContent(): void {
?>
		<h1>post track7 update</h1>
		<div id=editupdate></div>
<?php
	}
}
new NewUpdate();
