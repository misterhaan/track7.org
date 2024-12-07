<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class VoteHistory extends Page {
	public function __construct() {
		parent::__construct('votes');
	}

	protected static function MainContent(): void {
?>
		<h1>votes</h1>

		<div id=votes></div>
<?php
	}
}
new VoteHistory();
