<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class GitPull extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('update from github');
	}

	protected static function MainContent(): void {
?>
		<h1>update track7 from github</h1>

		<div id=gitpull></div>
<?php
	}
}
new GitPull();
