<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('hosted guestbooks');
?>
			<h1>hosted guestbooks</h1>
<?php
$books = 'select name from track7_t7data.gbbooks';
if($books = $db->query('select name from track7_t7data.gbbooks'))
	if($books->num_rows) {
?>
			<ul>
<?php
		while($book = $books->fetch_object()) {
?>
				<li><a href="view.php?book=<?=$book->name; ?>"><?=$book->name; ?></a></li>
<?php
		}
?>
			</ul>
<?php
	} else {
?>
			<p>no hosted guestbooks found.</p>
<?php
	}
else {
?>
			<p class=error>error reading list of guestbooks from database.</p>
<?php
}
$html->Close();
