<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class TimestampTest extends Page {
	public function __construct() {
		parent::__construct('timestamp converter');
	}

	protected static function MainContent(): void {
?>
		<h1>timestamp converter</h1>

		<div id=timestamps></div>
<?php
	}
}
new TimestampTest();
