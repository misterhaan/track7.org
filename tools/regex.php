<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class RegexTest extends Page {
	public function __construct() {
		parent::__construct('regular expression testing');
	}

	protected static function MainContent(): void {
?>
		<h1>regular expression testing</h1>

		<div id=regextest class=tabbed></div>
<?php
	}
}
new RegexTest();
