<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class BitlyTest extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('bitly test');
	}

	protected static function MainContent(): void {
?>
		<h1>bitly test</h1>
		<p>
			anything entered into this form gets sent to bit.ly and shortened using
			the track7 account.
		</p>

		<form method=post>
			<label title="enter a url to shorten">
				<span class=label>url:</span>
				<span class=field><input name=url id=url></span>
			</label>
			<button title="shorten this url with bit.ly">shorten</button>
		</form>
		<?php
		if (isset($_POST['url'])) {
			require_once 'formatUrl.php';
			require_once 'bitly.php';
			$url = Bitly::Shorten(trim($_POST['url']));
		?>
			<pre><code><?= $url; ?></code></pre>
<?php
		}
	}
}
new BitlyTest();
