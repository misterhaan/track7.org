<?php
define('TR_MESSAGES', 4);
define('TR_LEGOS', 7);
define('TR_STORIES', 8);
define('TR_UPDATES', 10);
define('TR_EXT_PROFILES', 12);
define('TR_GUESTBOOK', 13);

require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$db->real_query('create table if not exists transition_status (id tinyint unsigned primary key not null, stepnum tinyint not null default 0, status varchar(64) not null default \'not started\')');

$status = [];
if ($ss = $db->query('select id, stepnum, status from transition_status'))
	while ($s = $ss->fetch_object())
		$status[$s->id] = $s;

initStatus(TR_MESSAGES);
initStatus(TR_LEGOS);
initStatus(TR_STORIES);
initStatus(TR_UPDATES);
initStatus(TR_EXT_PROFILES);
initStatus(TR_GUESTBOOK);

$html = new t7html([]);
$html->Open('database transitions');
?>
<h1>database transitions</h1>

<ul>
	<li><a href=users.php>users</a></li>
	<li><a href=photos.php>photo album</a></li>
	<li><a href=art.php>art</a></li>
	<li><a href=blog.php>blog</a></li>
	<li><a href=code.php>code</a></li>
	<li><a href=forum.php>forum</a></li>
	<li><a href=guides.php>guides</a></li>
</ul>

<table>
	<thead>
		<tr>
			<th>subject</th>
			<th class=number>step</th>
			<th>status</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><a href="messages.php">messages</a></td>
			<td><?php echo $status[TR_MESSAGES]->stepnum; ?></td>
			<td><?php echo $status[TR_MESSAGES]->status; ?></td>
		</tr>
		<tr>
			<td><a href="legos.php">legos</a></td>
			<td><?php echo $status[TR_LEGOS]->stepnum; ?></td>
			<td><?php echo $status[TR_LEGOS]->status; ?></td>
		</tr>
		<tr>
			<td><a href="stories.php">stories</a></td>
			<td><?php echo $status[TR_STORIES]->stepnum; ?></td>
			<td><?php echo $status[TR_STORIES]->status; ?></td>
		</tr>
		<tr>
			<td><a href="updates.php">site updates</a></td>
			<td><?php echo $status[TR_UPDATES]->stepnum; ?></td>
			<td><?php echo $status[TR_UPDATES]->status; ?></td>
		</tr>
		<tr>
			<td><a href="extprofiles.php">external profiles</a></td>
			<td><?php echo $status[TR_EXT_PROFILES]->stepnum; ?></td>
			<td><?php echo $status[TR_EXT_PROFILES]->status; ?></td>
		</tr>
		<tr>
			<td><a href="guestbook.php">guestbook</a></td>
			<td><?= $status[TR_GUESTBOOK]->stepnum; ?></td>
			<td><?= $status[TR_GUESTBOOK]->status; ?></td>
		</tr>
	</tbody>
</table>
<?php
$html->Close();

function initStatus($id) {
	global $status, $db;
	if (!isset($status[$id])) {
		$status[$id] = (object)['stepnum' => 0, 'status' => 'not started'];
		$db->real_query('insert into transition_status set id=\'' . $id . '\', stepnum=0, status=\'not started\'');
	}
}
