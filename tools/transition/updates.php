<?php
define('TR_UPDATES', 10);
define('STEP_COPY_UPDATES', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('track7 updates migration');
?>
			<h1>track7 updates migration</h1>
<?php
if(isset($_GET['dostep']))
	switch($_GET['dostep']) {
		case 'copyupdates':
			if($db->real_query('insert into update_messages (posted, html) select instant, concat(\'<p>\', replace(replace(replace(`change`, \'&lsquo;\', \'’\'), \'&mdash;\', \'—\'), \'&nbsp;\', \' \'), \'</p>\') from track7_t7data.updates order by instant'))
				$db->real_query('update transition_status set stepnum=' . STEP_COPY_UPDATES . ', status=\'updates copied\' where id=\'' . TR_UPDATES . '\' and stepnum<' . STEP_COPY_UPDATES);
			else
				echo '<p>error migrating updates:  ' . $db->error . '</p>';
			break;
	}

if($status = $db->query('select stepnum, status from transition_status where id=' . TR_UPDATES))
	$status = $status->fetch_object();
?>
			<h2>updates</h2>
<?php
if($status->stepnum < STEP_COPY_UPDATES) {
	?>
			<p>
				the old updates need to be copied forward to the new database to enable
				comments.
			</p>
			<nav class=calltoaction><a class="new action" href="?dostep=copyupdates">copy those updates!</a></nav>
<?php
} else {
?>
			<p>updates migrated successfully.</p>
<?php
}
$html->Close();