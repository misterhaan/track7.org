<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class Conversations extends Page {
	public function __construct() {
		parent::__construct('messages');
	}

	protected static function MainContent(): void {
?>
		<h1>track7 messages</h1>

		<?php
		if (self::IsUserLoggedIn()) {
		?>
			<div id=messages></div>
		<?php
		} else {
		?>
			<p>
				hello, mysterious stranger! while we welcome your messages to track7
				users, we suggest you either sign in or leave a contact e-mail or url
				where you can receive a response.
			</p>
			<div id=sendmessage>
			</div>
<?php
		}
	}
}
new Conversations();
