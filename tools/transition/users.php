<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['markcomplete']))
	if($db->real_query('update transition_status set stepnum=2, status=\'finished migrating users\' where id=1')) {
		header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
		die;
	}

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/.dbinfo.track7.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/auText.php';

$olddb = new mysqli(_DB_HOST, _DB_USER, _DB_PASS, _DB_NAME);
$olddb->real_query('set names \'utf8\'');
$olddb->set_charset('utf8');

$html = new t7html([]);
$html->Open('user migration');
?>
			<h1>user migration</h1>
<?php
if(isset($_GET['migrate'])) {
	$uid = trim($_GET['migrate']);
	if($ou = $olddb->query('select login, pass, flags, tzoffset from users where uid=\'' . $olddb->real_escape_string($uid) . '\' limit 1'))
		if($ou = $ou->fetch_object()) {
			if($op = $olddb->query('select avatar from userprofiles where uid=\'' . $olddb->real_escape_string($uid) . '\' limit 1'))
				$op = $op->fetch_object();
			$ou->admin = $ou->flags & 0x80;
			$ou->dst = $ou->flags & 2;
			$avatar = ($op && $op->avatar) ? '/user/avatar/' . $ou->login . '.' . $op->avatar : '';
			$db->query('insert into users (level, username, avatar) values (\'' . +($ou->admin ? t7user::LEVEL_ADMIN : t7user::LEVEL_KNOWN) . '\', \'' . $olddb->real_escape_string($ou->login) . '\', \'' . $avatar . '\')');
			$id = $db->insert_id;
			$db->real_query('insert into users_settings (id, timebase, timeoffset) values (\'' . $db->real_escape_string($id) . '\', \'' . ($ou->dst ? 'server' : 'gmt') . '\', \'' . $db->real_escape_string($ou->tzoffset) . '\')');
			$db->real_query('insert into transition_users (id, olduid) values (\'' . $db->real_escape_string($id) . '\', \'' . $db->real_escape_string($uid) . '\')');
			$db->real_query('insert into transition_login (id, login, pass) values (\'' . $db->real_escape_string($id) . '\', \'' . $db->real_escape_string($ou->login) . '\', \'' . $db->real_escape_string($ou->pass) . '\')');
			if($oc = $olddb->query('select email, website, twitter, steam, flags from usercontact where uid=\'' . $db->real_escape_string($uid) . '\' limit 1'))
				if($oc = $oc->fetch_object()) {
					$db->real_query('insert into users_email (id, email, vis_email) values (\'' . $db->real_escape_string($id) . '\', \'' . $db->real_escape_string($oc->email) . '\', \'' . ($oc->flags & 1 ? 'all' : 'none') . '\')');
					$db->real_query('insert into users_profiles (id, website, twitter, steam) values (\'' . $db->real_escape_string($id) . '\', \'' . $db->real_escape_string($oc->website) . '\', \'' . $db->real_escape_string($oc->twitter) . '\', \'' . $db->real_escape_string($oc->steam) . '\')');
				}
			if($os = $olddb->query('select since, lastlogin from userstats where uid=\'' . $olddb->real_escape_string($uid) . '\' limit 1'))
				if($os = $os->fetch_object())
					$db->real_query('insert into users_stats (id, registered, lastlogin) values (\'' . $db->real_escape_string($id) . '\', \'' . +$os->since . '\', \'' . +$os->lastlogin . '\')');
			$newid = [];
			if($trans = $db->query('select id, olduid from transition_users'))
				while($tran = $trans->fetch_object())
					$newid[$tran->olduid] = $tran->id;
			if(count($newid)) {
				$friends = [];
				if($ofs = $olddb->query('select frienduid from userfriends where fanuid=\'' . $olddb->real_escape_string($uid) . '\''))
					while($of = $ofs->fetch_object())
						if(isset($newid[$of->frienduid]))
							$friends[] = +$newid[$of->frienduid];
				if(count($friends))
					$db->real_query('insert into users_friends (fan, friend) values (\'' . $db->real_escape_string($id) . '\', \'' . implode('\'), (\'' . +$id . '\', \'', $friends) . '\')');
				$fans = [];
				if($ofs = $olddb->query('select fanuid from userfriends where frienduid=\'' . $olddb->real_escape_string($uid) . '\''))
					while($of = $ofs->fetch_object())
						if(isset($newid[$of->fanuid]))
							$fans[] = +$newid[$of->fanuid];
				if(count($fans))
					$db->real_query('insert into users_friends (friend, fan) values (\'' . $db->real_escape_string($id) . '\', \'' . implode('\'), (\'' . +$id . '\', \'', $fans) . '\')');
				if($updatefancount = $db->prepare('update users_stats set fans=? where id=?'))
					if($updatefancount->bind_param('ii', $fancount, $friendid))
						if($fcs = $db->query('select friend, count(fan) as fans from users_friends group by friend'))
							while($fc = $fcs->fetch_object()) {
								$friendid = $fc->friend;
								$fancount = $fc->fans;
								$updatefancount->execute();
							}
			}
			$db->real_query('update transition_status set stepnum=1, status=\'some users converted\' where id=1 and stepnum<2');
		}
}
$oldusers = 'select u.uid, u.login, s.since, s.lastlogin, s.rank, s.fans, s.posts, s.comments, s.rounds from users as u, userstats as s where u.uid=s.uid and s.fans+s.signings+s.comments+s.posts+s.discs+s.rounds>0 order by s.posts+s.comments/2+s.rounds/4 desc';
if($oldusers = $olddb->query($oldusers)) {
	if($migratedusers = $db->query('select olduid from transition_users')) {
		$migrated = [];
		while($migrateduser = $migratedusers->fetch_object())
			$migrated[] = $migrateduser->olduid;
?>
			<table>
				<thead><tr>
					<th>user</th>
					<th class=number>id</th>
					<th>frequency</th>
					<th>last login</th>
					<th>registered</th>
					<th class=number>fans</th>
					<th class=number>posts</th>
					<th class=number>comments</th>
					<th class=number>rounds</th>
					<th>migrate</th>
				</tr></thead>
				<tbody>
<?php
		while($u = $oldusers->fetch_object()) {
?>
					<tr>
						<td><a href="/user/<?php echo $u->login; ?>/" title="view <?php echo $u->login; ?>’s profile"><?php echo $u->login; ?></a></td>
						<td class=number><?php echo $u->uid; ?></td>
						<td><?php echo $u->rank; ?></td>
						<td><?php echo $u->lastlogin == null ? '' : auText::HowLongAgo($u->lastlogin) . ' ago'; ?></td>
						<td><?php echo $u->since == null ? '' : auText::SmartTime($u->since, $user); ?></td>
						<td class=number><?php echo $u->fans; ?></td>
						<td class=number><?php echo $u->posts; ?></td>
						<td class=number><?php echo $u->comments; ?></td>
						<td class=number><?php echo $u->rounds; ?></td>
						<td><?php echo in_array($u->uid, $migrated) ? 'migrated' : '<a href="?migrate=' . $u->uid . '">migrate</a>'; ?></td>
					</tr>
<?php
		}
?>
				</tbody>
			</table>
<?php
	}
}
?>
			<nav class=actions><a href="?markcomplete">mark user migration complete</a></nav>
<?php
$html->Close();

function onlineIcon($pageload) {
	$on = $pageload > time() -900;
	return '<img src="/images/' . ($on ? 'online' : 'offline') . '.png" alt='. ($on ? 'online' : 'offline') . ' title="' . ($on ? 'was here in the past 15 minutes' : 'hasn’t been here in the past 15 minutes') . '">';
}
