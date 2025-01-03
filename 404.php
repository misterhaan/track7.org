<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class NotFound extends Page {
	public function __construct() {
		self::NotFound();
	}

	protected static function MainContent(): void {
		// this won't get run because the NotFound() call takes care of everything
	}
}
new NotFound();
