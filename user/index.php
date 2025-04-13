<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class UserIndex extends Page {
	public function __construct() {
		parent::__construct('users');
	}

	protected static function MainContent(): void {
		// everything is in the javascript file
	}
}
new UserIndex();
