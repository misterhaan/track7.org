<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class StartDiscussion extends Page {
	public function __construct() {
		parent::__construct('new discussion');
	}

	protected static function MainContent(): void {
?>
		<h1>start new discussion</h1>
		<div id=editdiscussion></div>
<?php
	}
}
new StartDiscussion();
