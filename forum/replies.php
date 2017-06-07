<?php
define('MAX_THREADS', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'list': GetReplies(); break;
		case 'update': UpdateReply(); break;
		case 'stealthupdate': UpdateReply(false); break;
		case 'delete': DeleteReply(); break;
	}
	$ajax->Send();
	die;
}

$u = FindUser();

$html = new t7html(['ko' => true]);
if($u) {
	$html->Open(htmlspecialchars($u->displayname) . '’s latest replies');
?>
			<h1 data-user=<?php echo $u->id; ?>>
				<a href="/user/<?php echo $u->username; ?>/">
					<img class="inline avatar" src="<?php echo $u->avatar; ?>">
					<?php echo htmlspecialchars($u->displayname); ?></a>’s latest replies
			</h1>
<?php
} else {
	$html->Open('latest replies');
?>
			<h1>
				latest replies
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of the forum"><img alt=feed src="/images/feed.png"></a>
			</h1>
<?php
}
?>
			<p class=info data-bind="visible: !loading() && replies().length == 0">no forum activity</p>

			<!-- ko foreach: replies -->
			<h2><a data-bind="text: title, attr: {href: '<?php echo dirname($_SERVER['PHP_SELF']); ?>/' + discussion + '#r' + id}"></a></h2>
			<section class=comment data-bind="attr: {id: 'r' + id}">
				<div class=userinfo>
					<!-- ko if: username -->
					<div class=username>
						<a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a>
						<img data-bind="visible: friend, attr: {title: (displayname || username) + ' is your friend'}" alt="*" src="/images/friend.png">
					</div>
					<a data-bind="visible: avatar, attr: {href: '/user/' + username + '/'}"><img class=avatar alt="" data-bind="attr: {src: avatar}"></a>
					<div class=userlevel data-bind="visible: level, text: level"></div>
					<!-- /ko -->
					<!-- ko if: !username && contacturl -->
					<div class=username><a data-bind="text: name, attr: {href: contacturl}"></a></div>
					<!-- /ko -->
					<!-- ko if: !username && !contacturl -->
					<div class=username data-bind="text: name"></div>
					<!-- /ko -->
				</div>
				<div class=comment>
					<header>posted <time data-bind="text: posted.display, attr: {datetime: posted.datetime}"></time></header>
					<div class=content data-bind="visible: !editing(), html: html"></div>
					<div class="content edit" data-bind="visible: editing"><textarea data-bind="value: markdown"></textarea></div>
					<div class=meta data-bind="foreach: edits">
						<div class=edithistory>
							edited
							<time data-bind="text: posted, attr: {datetime: datetime}"></time>
							by
							<a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a>
						</div>
					</div>
					<footer data-bind="visible: canchange">
						<!-- ko if: editing() -->
						<a class="okay action" data-bind="click: $parent.SaveReply" href="?ajax=update">save</a>
<?php
if($user->IsTrusted()) {
?>
						<a class="okay action" data-bind="click: $parent.StealthSaveReply" href="?ajax=stealthupdate">stealth save</a>
<?php
}
?>
						<a class="cancel action" data-bind="click: $parent.UneditReply" href="#cancel">cancel</a>
						<!-- /ko -->
						<!-- ko ifnot: editing() -->
						<a class="edit action" data-bind="click: $parent.EditReply" href="#edit">edit</a>
						<a class="del action" data-bind="click: $parent.DeleteReply" href="?ajax=delete">delete</a>
						<!-- /ko -->
					</footer>
				</div>
			</section>

			<!-- /ko -->

			<p class=loading data-bind="visible: loading()">loading replies . . .</p>

			<p class=calltoaction data-bind="visible: more() && !loading()"><a class="action get" href="#load" data-bind="click: Load">load more replies</a></p>
<?php
$html->Close();

function FindUser() {
	global $db;
	if(isset($_GET['username'])) {
		if($u = $db->prepare('select id, username, displayname, avatar from users where username=? limit 1'))
			if($u->bind_param('s', $_GET['username']))
				if($u->execute())
					if($u->bind_result($id, $username, $displayname, $avatar))
						if($u->fetch())
							return (object)['id' => $id, 'username' => $username, 'displayname' => $displayname ? $displayname : $username, 'avatar' => $avatar ? $avatar : t7user::DEFAULT_AVATAR];
		if(substr($_SERVER['REQUEST_URI'], 0, 6) == '/user/') {
			header('Location: ' . t7format::FullUrl($_SERVER['PHP_SELF']));
			die;
		}
	}
	return false;
}

function GetReplies() {
	global $ajax, $db, $user;
	$before = isset($_GET['before']) && $_GET['before'] ? +$_GET['before'] : time() + 43200;
	$userid = isset($_GET['userid']) && $_GET['userid'] ? +$_GET['userid'] : false;
	if($rs = $db->query('select r.discussion, d.title, r.id, r.posted, r.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, r.name, r.contacturl, r.markdown, r.html, group_concat(concat(e.posted, \'\t\', eu.username, \'\t\', eu.displayname) order by e.posted separator \'\n\') as edits from forum_replies as r left join forum_discussions as d on d.id=r.discussion left join users as u on u.id=r.user left join users_friends as f on f.friend=r.user and f.fan=\'' . +$user->ID . '\' left join forum_edits as e on e.reply=r.id left join users as eu on eu.id=e.editor where r.posted<\'' . $before . ($userid ? '\' and r.user=\'' . $userid : '') . '\' group by r.id order by r.posted desc limit ' . MAX_THREADS)) {
		$ajax->Data->replies = [];
		$ajax->Data->latest = 0;
		while($r = $rs->fetch_object()) {
			$ajax->Data->latest = $r->posted;
			$r->posted = t7format::TimeTag('g:i a \o\n l F jS Y', $r->posted);
			if(!$user->IsLoggedIn() && substr($r->contacturl, 0, 7) == 'mailto:')
				$r->contacturl = '';
			$r->canchange = $user->IsLoggedIn() && ($r->canchange == $user->ID && $r->markdown || $user->IsAdmin());
			if($r->edits) {
				$edits = [];
				foreach(explode("\n", $r->edits) as $e) {
					list($posted, $username, $display) = explode("\t", $e);
					$edits[] = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate('g:i a \o\n l F jS Y', $posted)), 'username' => $username, 'displayname' => $display];
				}
				$r->edits = $edits;
			}
			if(!$r->canchange)
				unset($r->markdown);
			elseif(!$r->markdown && $user->IsAdmin())
				$r->markdown = $r->html;
			if($r->avatar === '')
				$r->avatar = t7user::DEFAULT_AVATAR;
			$ajax->Data->replies[] = $r;
		}
		if($more = $db->query('select count(1) as num from forum_replies where posted<\'' . +$ajax->Data->latest . '\'' . ($userid ? ' and user=\'' . $userid . '\'' : '')))
			if($more = $more->fetch_object())
				$ajax->Data->more = $more->num;
	} else
		$ajax->Fail('error looking up replies:  ' . $db->error);
}

function UpdateReply($history = true) {
	global $ajax, $db, $user;
	if(isset($_POST['reply']) && isset($_POST['markdown']))
		if($user->IsLoggedIn()) {
			$reply = +$_POST['reply'];
			$markdown = trim($_POST['markdown']);
			$html = t7format::Markdown($markdown);
			$uid = $user->ID;
			if($update = $db->prepare('update forum_replies set markdown=?, html=? where id=? and (user=? or ? in (select id from users where level=\'' . +t7user::LEVEL_ADMIN . '\'))'))
				if($update->bind_param('ssiii', $markdown, $html, $reply, $uid, $uid))
					if($update->execute())
						if($update->affected_rows) {
							$ajax->Data->html = $html;
							$update->close();
							if($history || !$user->IsTrusted())
								if($ins = $db->prepare('insert into forum_edits (reply, editor, posted) values (?, ?, ?)')) {
									$posted = time();
									if($ins->bind_param('iii', $reply, $uid, $posted))
										if($ins->execute())
											$ajax->Data->edit = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate('g:i a \o\n F jS Y', $posted)), 'username' => $user->Username, 'displayname' => $user->DisplayName];
										else
											$ajax->Data->edit = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate('g:i a \o\n F jS Y', $posted)), 'username' => $user->Username, 'displayname' => 'error executing edit history update:  ' . $ins->error];
									else
										$ajax->Data->edit = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate('g:i a \o\n F jS Y', $posted)), 'username' => $user->Username, 'displayname' => 'error binding parameters to update edit history:  ' . $ins->error];
								} else
									$ajax->Data->edit = ['datetime' => time(), 'posted' => strtolower(t7format::LocalDate('g:i a \o\n F jS Y', time())), 'username' => $user->Username, 'displayname' => 'error preparing to update edit history:  ' . $db->error];
						} else
							$ajax->Fail('reply not changed.  either it’s not yours or you saved it without any changes.');
					else
						$ajax->Fail('error executing reply edit:  ' . $update->error);
				else
					$ajax->Fail('error binding parameters to edit reply:  ' . $update->error);
			else
				$ajax->Fail('error preparing to edit reply:  ' . $db->error);
		} else
			$ajax->Fail('cannot edit post because you are no longer logged in');
	else
		$ajax->Fail('required fields missing');
}

function DeleteReply() {
	global $ajax, $db, $user;
	if(isset($_POST['reply']))
		if($user->IsLoggedIn()) {
			$reply = +$_POST['reply'];
			$uid = $user->ID;
			if($del = $db->prepare('delete from forum_replies where id=? and (user=? or ? in (select id from users where level=\'' . +t7user::LEVEL_ADMIN . '\'))'))
				if($del->bind_param('iii', $reply, $uid, $uid))
					if($del->execute())
						if($del->affected_rows) {
							if(!$db->real_query('update users_stats as us set us.replies=(select count(1) from forum_replies where user=us.id)'))
								$ajax->Fail('error updating user stats:  ' . $db->error);
							if($db->real_query('delete from forum_discussion_tags where discussion not in (select distinct discussion from forum_replies)')) {
								if($db->affected_rows)
									if(!$db->real_query('update forum_tags as t set count=(select count(1) from forum_discussion_tags where tag=t.id group by tag), lastused=(select min(r.posted) from forum_replies as r left join forum_discussion_tags as dt on dt.discussion=r.discussion where dt.tag=t.id group by dt.tag)'))
										$ajax->Fail('error updating tag statistics:  ' . $db->error);
							} else
								$ajax->Fail('error deleting tags from empty discussions:  ' . $db->error);
							if($db->query('delete from forum_discussions where id not in (select distinct discussion from forum_replies)'))
								$ajax->Data->deletedDiscussions = $db->affected_rows;
							else
								$ajax->Fail('error deleting empty discussions:  ' . $db->error);
							$ajax->Data->deleted = $del->affected_rows . ' rows deleted.  reply id ' . $reply;
						}
						else
							$ajax->Fail('reply not deleted probably because you don’t have permission.');
					else
						$ajax->Fail('error executing reply deletion:  ' . $del->error);
				else
					$ajax->Fail('error binding parameters to delete reply:  ' . $del->error);
			else
				$ajax->Fail('error preparing to delete reply:  ' . $db->error);
		} else
			$ajax->Fail('cannot delete post because you are no longer logged in');
	else
		$ajax->Fail('required field missing');
}
