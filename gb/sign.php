<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('sign guestbook');
?>
			<h1>sign guestbook</h1>
			<p>
				sorry, but track7 no longer provides hosted guestbook services.  you may
				<a href="view.php?book=<?=htmlspecialchars($_GET['book']); ?>">view this guestbook</a>,
				but may not sign it.
			</p>
<?php
$html->Close();
