<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class TokensTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('tokens');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckTokenTable();
	}

	private static function CheckTokenTable(): void {
		if (self::CheckTableExists('token')) {
?>
			<p>new <code>token</code> table exists.</p>
<?php
			self::Done();
		} else
			self::CreateTable('token');
	}
}
new TokensTransition();
