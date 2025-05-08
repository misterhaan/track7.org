<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class ToolsMenu extends Page {
	public function __construct() {
		parent::__construct('track7 administrative tools');
	}

	protected static function MainContent(): void {
?>
		<h1>track7 administrative tools</h1>

		<h2>manage code</h2>
		<ul>
			<li><a href="git-pull.php">update track7 from github</a></li>
			<li><a href="https://github.com/misterhaan/track7.org">track7 project on github</a></li>
		</ul>

		<h2>manage data</h2>
		<ul>
			<li><a href="transition/">transitions</a></li>
			<li><a href="/votes.php">votes</a></li>
			<li><a href="/tags.php">tags</a></li>
			<?php
			if (self::IsTestServer()) {
			?>
				<li><a href="/phpmyadmin/?db=track7">phpmyadmin</a></li>
			<?php
			} else {
			?>
				<li><a href="/dh_phpmyadmin/data.track7.org/?db=track7">phpmyadmin</a></li>
				<li><a href="https://panel.dreamhost.com/">dreamhost panel</a></li>
			<?php
			}
			?>
		</ul>

		<h2>test php functions</h2>
		<ul>
			<li><a href="bitly.php">bitly</a></li>
			<li><a href="tweet.php">tweet</a></li>
			<li><a href="regex.php">regular expressions</a></li>
			<li><a href="timestamps.php">timestamps</a></li>
		</ul>

		<h2>view server settings</h2>
		<ul>
			<li><a href="info.php">phpinfo</a></li>
			<li><a href="server.php">$_SERVER array contents</a></li>
			<li><a href="ini.php">php.ini settings</a></li>
		</ul>
<?php
	}
}
new ToolsMenu();
