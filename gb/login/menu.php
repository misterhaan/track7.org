<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('guestbook management');
?>
			<h1>guestbook management</h1>
			<p>
				sorry, but track7 no longer provides hosted guestbook services.  you may
				<a href="..">view hosted guestbooks</a>, but may not modify them.
			</p>
<?php
$html->Close();
