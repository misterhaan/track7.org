<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('track7 administrative tools');
?>
			<h1>track7 administrative tools</h1>

			<h2>manage code</h2>
			<ul>
				<li><a href="git-pull.php">update track7 from github</a></li>
				<li><a href="https://github.com/misterhaan/track7.org">track7 project on github</a></li>
			</ul>

			<h2>manage data</h2>
			<ul>
				<li><a href="transition/">bring data forward from old database</a></li>
				<li><a href="/votes.php">votes</a></li>
				<li><a href="/tags.php">tag info</a></li>
<?php
if($_SERVER['SERVER_PORT'] == 80) {
?>
				<li><a href="/dh_phpmyadmin/data.track7.org/?db=track7">phpmyadmin</a></li>
				<li><a href="https://panel.dreamhost.com/">dreamhost panel</a></li>
<?php
} else {
?>
				<li><a href="/phpmyadmin/?db=track7">phpmyadmin</a></li>
<?php
}
?>
			</ul>

			<h2>test php functions</h2>
			<ul>
				<li><a href="bitly.php">t7send::Bitly tester</a></li>
				<li><a href="tweet.php">t7send::Tweet tester</a></li>
				<li><a href="regex.php">regular expression tester</a></li>
				<li><a href="timestamps.php">timestamp converter</a></li>
			</ul>

			<h2>view server settings</h2>
			<ul>
				<li><a href="info.php">phpinfo</a></li>
				<li><a href="server.php">$_SERVER array contents</a></li>
				<li><a href="ini.php">php.ini settings</a></li>
			</ul>

<?php
$html->Close();
