<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class TweetTest extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('tweet test');
	}

	protected static function MainContent(): void {
?>
		<h1>tweet test</h1>
		<p>
			anything entered into this form gets sent to <a href="https://twitter.com/track7feed">twitter</a>,
			so remember to delete test tweets.
		</p>
		<form method=post>
			<label title="enter a message to tweet">
				<span class=label>message:</span>
				<span class=field><input name=message id=message></span>
			</label>
			<label title="enter a url to send with the tweet (optional)">
				<span class=label>url:</span>
				<span class=field><input name=url id=url></span>
			</label>
			<button>tweet</button>
		</form>
		<?php
		if (isset($_POST['message'])) {
			require_once 'twitter.php';
			$tweet = Twitter::Tweet(trim($_POST['message']), trim($_POST['url']));
		?>
			<h2>response code <?= $tweet->code; ?></h2>
			<pre><code><?= $tweet->text; ?></code></pre>
<?php
		}
	}
}
new TweetTest();
