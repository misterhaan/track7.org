<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'twitter.php';

class TweetTest extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('tweet test');
	}

	protected static function MainContent(): void {
?>
		<h1>tweet setup and testing</h1>
		<div id=tweet></div>

<?php
	}
}
new TweetTest();
