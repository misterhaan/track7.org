<?php
if(isset($_GET['book'])) {
	require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
	if($book = $db->query('select id, header, footer from track7_t7data.gbbooks where name=\'' . $db->escape_string($_GET['book']) . '\' limit 1'))
		if($book = $book->fetch_object()) {
			echo $book->header;
			if($entries = $db->query('select entry from track7_t7data.gbentries where bookid=\'' . +$book->id . '\' order by id desc'))
				if($entries->num_rows)
					while($entry = $entries->fetch_object())
						echo $entry->entry;
				else
					echo '<p>there are no entries in this guestbook yet.</p>';
			else
				echo 'error reading database when trying to look up entries for this guestbook:<br>' . $db->error;
			echo $book->footer;
		} else
			echo 'could not find a guestbook named \'' . htmlspecialchars($_GET['book']) . '\' in the database.';
	else
		echo 'database error trying to look up guestbook:<br>' . $db->error;
} else
	echo 'no guestbook to view!';
