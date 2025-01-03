<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class AuthRequired extends Page {
	public function __construct() {
		parent::__construct('401 authorization required');
	}

	protected static function MainContent(): void {
?>
		<h1>401 you are not me</h1>

		<p>
			the page you requested is only for me, and since you did not prove that
			you are me, you cannot see it.
		</p>

		<p class=calltoaction><a href="/" class=action>go to the track7 front page</a></p>

<?php
	}
}
new AuthRequired();
