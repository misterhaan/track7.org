<?php
define('TR_FORUM', 11);
define('STEP_COPYTHREADS', 1);
define('STEP_COPYTAGS', 2);
define('STEP_LINKTAGS', 3);
define('STEP_COPYREPLIES', 4);
define('STEP_COUNTTAGS', 5);
define('STEP_COPYEDITS', 6);
define('STEP_UPDATEUSERSTATS', 7);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('forum');
?>
			<h1>forum migration</h1>
<?php
if(isset($_GET['dostep']))
	switch($_GET['dostep']) {
		case 'copythreads':
			if($db->real_query('insert into forum_discussions (title, threadid) select replace(title, \'&quot;\', \'"\'), id from track7_t7data.hbthreads order by instant'))
				$db->real_query('update transition_status set stepnum=' . STEP_COPYTHREADS . ', status=\'thread titles copied\' where id=' . TR_FORUM . ' and stepnum<' . STEP_COPYTHREADS);
			else
				ShowError('error copying thread titles:  ' . $db->error);
			break;
		case 'copytags':
			if($db->real_query('insert into forum_tags (name, description) select name, concat(\'<p>\',replace(description, \'&nbsp;\', \' \'),\'</p>\') from track7_t7data.taginfo where type=\'threads\' and count>0 and name!=\'\''))
				$db->real_query('update transition_status set stepnum=' . STEP_COPYTAGS . ', status=\'thread tags copied\' where id=' . TR_FORUM . ' and stepnum<' . STEP_COPYTAGS);
			else
				ShowError('error copying thread tags:  ' . $db->error);
			break;
		case 'linktags':
			if($thtags = $db->query('select t.tags, d.id from track7_t7data.hbthreads as t left join forum_discussions as d on d.threadid=t.id'))
				if($ins = $db->prepare('insert into forum_discussion_tags (tag, discussion) values ((select id from forum_tags where name=? limit 1), ?)'))
					if($ins->bind_param('si', $tag, $disc)) {
						while($thtag = $thtags->fetch_object()) {
							$disc = $thtag->id;
							$thtag->tags = explode(',', $thtag->tags);
							foreach($thtag->tags as $tag)
								$ins->execute();
						}
						$db->real_query('update transition_status set stepnum=' . STEP_LINKTAGS . ', status=\'thread tags linked\' where id=' . TR_FORUM . ' and stepnum<' . STEP_LINKTAGS);
					} else
						ShowError('error binding parameters for tag link migration:  ' . $ins->error);
				else
					ShowError('error preparing to migrate tag links:  ' . $db->error);
			else
				ShowError('error looking up tag links:  ' . $db->error);
			break;
		case 'copyreplies':
			if($db->real_query('insert into forum_replies (discussion, posted, user, html, postid) select d.id, p.instant, tu.id, replace(p.post, \'&nbsp;\', \' \'), p.id from track7_t7data.hbposts as p left join transition_users as tu on tu.olduid=p.uid left join forum_discussions as d on d.threadid=p.thread order by p.instant')) {
				$db->real_query('update forum_replies set name=\'random internet person\' where user is null');
				$db->real_query('update transition_status set stepnum=' . STEP_COPYREPLIES . ', status=\'posts copied\' where id=' . TR_FORUM . ' and stepnum<' . STEP_COPYREPLIES);
			} else
				ShowError('error copying replies:  ' . $db->error);
			break;
		case 'counttags':
			if($db->real_query('update forum_tags as t set count=(select count(1) from forum_discussion_tags where tag=t.id group by tag), lastused=(select min(r.posted) from forum_replies as r left join forum_discussion_tags as dt on dt.discussion=r.discussion where dt.tag=t.id group by dt.tag)'))
				$db->real_query('update transition_status set stepnum=' . STEP_COUNTTAGS . ', status=\'tags counted\' where id=' . TR_FORUM . ' and stepnum<' . STEP_COUNTTAGS);
			else
				ShowError('error counting tags:  ' . $db->error);
			break;
		case 'copyedits':
			if($edits = $db->query('select r.id, p.history from track7_t7data.hbposts as p left join forum_replies as r on r.postid=p.id where p.history!=\'\''))
				if($ins = $db->prepare('insert into forum_edits (reply, editor, posted) values (?, (select id from users where username=? limit 1), ?)'))
					if($ins->bind_param('isi', $reply, $username, $posted)) {
						while($edit = $edits->fetch_object()) {
							$reply = $edit->id;
							$edit->history = explode('/', substr($edit->history, 1));
							foreach($edit->history as $history) {
								list($username, $posted) = explode('|', $history);
								$ins->execute();
							}
						}
						$db->real_query('update transition_status set stepnum=' . STEP_COPYEDITS . ', status=\'edit history copied\' where id=' . TR_FORUM . ' and stepnum<' . STEP_COPYEDITS);
					} else
						ShowError('error binding parameters for edit history migration:  ' . $ins->error);
				else
					ShowError('error preparing to migrate edit history:  ' . $db->error);
			else
				ShowError('error looking up edit history:  ' . $db->error);
			break;
		case 'updateuserstats':
			if($db->real_query('update users_stats as u set comments=(select count(1) from contributions where conttype=\'comment\' and author=u.id), replies=(select count(1) from forum_replies where user=u.id)'))
				$db->real_query('update transition_status set stepnum=' . STEP_UPDATEUSERSTATS . ', status=\'user stats updated\' where id=' . TR_FORUM . ' and stepnum<' . STEP_UPDATEUSERSTATS);
			else
				ShowError('error updating user stats:'  . $db->error);
			break;
}

if($status = $db->query('select stepnum, status from transition_status where id=' . TR_FORUM))
	$status = $status->fetch_object();
?>
			<h2>threads</h2>
<?php
if($status->stepnum < STEP_COPYTHREADS) {
?>
			<p>
				make sure to create the forums tables, and add their triggers.  may as
				well add 'forum_replies' to contributions.srctbl and 'discuss' to
				contributions.conttype at the same time.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=copythreads">copy threads</a></nav>
<?php
} else {
?>
			<p>forum threads successfully copied.</p>

			<h2>tags</h2>
<?php
	if($status->stepnum < STEP_COPYTAGS) {
?>
			<p>
				make sure the forum_tags table exists, then forum tags can be copied.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=copytags">copy tags</a></nav>
<?php
	} else {
?>
			<p>forum tags have been successfully copied.</p>
<?php
		if($status->stepnum < STEP_LINKTAGS) {
?>
			<p>
				now that the tags exist, it’s time to apply them to the discussions.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=linktags">link tags</a></nav>
<?php
		} else {
?>
			<p>forum tags successfully linked.</p>

			<h2>replies</h2>
<?php
			if($status->stepnum < STEP_COPYREPLIES) {
?>
			<p>
				with the discussions (threads) in place, we’re ready to migrate the
				replies (posts).
			</p>
			<nav class=calltoaction><a class=action href="?dostep=copyreplies">copy replies</a></nav>
<?php
			} else {
?>
			<p>forum posts successfully copied.</p>

			<h2>tag analysis</h2>
<?php
				if($status->stepnum < STEP_COUNTTAGS) {
?>
			<p>
				with replies copied the tags can be analyzed to find when they were last
				used and how many times they were used.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=counttags">analyze tags</a></nav>
<?php
				} else {
?>
			<p>tags successfully analyzed.</p>

			<h2>edits</h2>
<?php
					if($status->stepnum < STEP_COPYEDITS) {
?>
			<p>
				posts allow editing, and they keep a history of when they were edited
				and who did it.  instead of stuffing that into one item the new database
				has a table for it.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=copyedits">copy edit history</a></nav>
<?php
					} else {
?>
			<p>edit history successfully copied.</p>

			<h2>users</h2>
<?php
						if($status->stepnum < STEP_UPDATEUSERSTATS) {
?>
			<p>
				user statistics include number of posts in the forum (new discussions
				and replies to existing discussions), so that needs to get set based on
				what’s been migrated.  also comments is probably out of date so may as
				well update that too.  make sure to update the users table to rename
				the posts column to replies.
			</p>
			<nav class=calltoaction><a class=action href="?dostep=updateuserstats">update user stats</a></nav>
<?php
						} else {
?>
			<p>user statistics successfully calculated.</p>

<?php
						}
					}
				}
			}
		}
	}
}
$html->Close();

function ShowError($message) {
?>
			<p class=error><?php echo $message; ?></p>
<?php
}
