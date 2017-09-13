<?php
define('MAX_COMMENT_GET', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'getall': GetAllComments(); break;
		case 'get': GetComments(); break;
		case 'add':
			if(VerifyCommonFields($ajax, $_POST))
				if($user->IsLoggedIn() || isset($_POST['name']))  // name can be blank but needs to have been sent if not logged in
					if(isset($_POST['md']) && !ctype_space($_POST['md']) && $_POST['md'] != '') {
						$ajax->Data->html = t7format::Markdown($_POST['md']);
						$ajax->Data->posted = +time();
						if(!$user->IsLoggedIn()) {
							$ajax->Data->name = trim($_POST['name']);
							if($ajax->Data->name == '')
								$ajax->Data->name = $user->DisplayName;  // grab the default display name for non-logged-in users
							$ajax->Data->contacturl = t7format::Link($_POST['contact']);
							$ajax->Data->canchange = false;
							$ajax->Data->friend = false;
							$ajax->Data->username = $ajax->Data->displayname = $ajax->Data->avatar = $ajax->Data->level = null;
						}
						$ins = $user->IsLoggedIn()
							? 'insert into ' . $_POST['type'] . '_comments (' . KeyName($_POST['type']) . ', posted, user, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $user->ID . '\', \'' . $db->escape_string($ajax->Data->html) . '\', \'' . $db->escape_string($_POST['md']) . '\')'
							: 'insert into ' . $_POST['type'] . '_comments (' . KeyName($_POST['type']) . ', posted, name, contacturl, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $db->escape_string($ajax->Data->name) . '\', \'' . $db->escape_string($ajax->Data->contacturl) . '\', \'' . $db->escape_string(t7format::Markdown($_POST['md'])) . '\', \'' . $db->escape_string($_POST['md']) . '\')';
						if($db->real_query($ins)) {
							$ajax->Data->id = $db->insert_id;
							if($act = $db->query('select title, url from contributions where srctbl=\'' . $_POST['type'] . '_comments\' and id=\'' . +$ajax->Data->id . '\''))
								if($act = $act->fetch_object())
									t7send::Tweet('comment on ' . $act->title, t7format::FullUrl($act->url));
							$ajax->Data->posted = t7format::TimeTag('g:i a \o\n l F jS Y', $ajax->Data->posted);
							if($user->IsLoggedIn()) {
								$db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$user->ID . '\' group by author) where id=\'' . +$user->ID . '\'');
								$ajax->Data->canchange = true;
								$ajax->Data->username = $user->Username;
								$ajax->Data->displayname = $user->DisplayName;
								$ajax->Data->friend = false;
								$ajax->Data->avatar = $user->Avatar;
								$ajax->Data->level = $user->GetLevelName();
								$ajax->Data->name = '';
								$ajax->Data->contacturl = '';
							}
						} else
							$ajax->Fail('error saving comment.');
					} else
						$ajax->Fail('comment missing or empty.');
			break;
		case 'save':
			if(VerifyCommonFields($ajax, $_POST, 'id'))
				if($user->IsLoggedIn())
					if(isset($_POST['markdown']) && trim($_POST['markdown']))
						if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
							if($comment = $comment->fetch_object())
								if($user->ID == $comment->user || $user->IsAdmin()) {
									$ajax->Data->html = t7format::Markdown($_POST['markdown']);
									if(!$db->real_query('update ' . $_POST['type'] . '_comments set markdown=\'' . $db->escape_string($_POST['markdown']) . '\', html=\'' . $db->escape_string($ajax->Data->html) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
										$ajax->Fail('error updating comment');
								} else
									$ajax->Fail('you can only edit comments you posted.');
							else
								$ajax->Fail('comment not found.');
						else
							$ajax->Fail('error looking up comment.');
					else
						$ajax->Fail('comment was empty.  if you intend to delete your comment, cancel editing and use delete instead.');
				else
					$ajax->Fail('you must be signed in to edit your comment.  you were probably signed out for inactivity.');
			break;
		case 'delete':
			if(VerifyCommonFields($ajax, $_POST, 'id'))
				if($user->IsLoggedIn())
					if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
						if($comment = $comment->fetch_object())
							if($user->ID == $comment->user || $user->IsAdmin())
								if($db->real_query('delete from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
									$db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$user->ID . '\' group by author) where id=\'' . +$user->ID . '\'');
								else
									$ajax->Fail('error deleting comment.');
							else
								$ajax->Fail('you can only delete comments you posted.');
						else
							$ajax->Fail('comment not found.');
					else
						$ajax->Fail('error looking up comment.');
				else
					$ajax->Fail('you must be signed in to delete your comment.  you were probably signed out for inactivity.');
			break;
		default:
			$ajax->Fail('unknown function name.  supported function names are: get, add, delete.');
			break;
	}
	$ajax->Send();
	die;
}

$u = FindUser();

$html = new t7html(['ko' => true]);
if($u) {
	$html->Open(htmlspecialchars($u->displayname) . '’s comments');
?>
			<h1 data-user=<?php echo $u->id; ?>>
				<a href="/user/<?php echo $u->username; ?>/">
					<img class="inline avatar" src="<?php echo $u->avatar; ?>">
					<?php echo htmlspecialchars($u->displayname); ?></a>’s comments
			</h1>
<?php
} else {
	$html->Open('comments');
?>
			<h1 data-user=all>
				comments
				<a class=feed href="<?php echo str_replace('.php', '.rss', $_SERVER['PHP_SELF']); ?>" title="rss feed of comments"><img alt=feed src="/images/feed.png"></a>
			</h1>
<?php
}
?>
			<div id=comments>
				<p class=info data-bind="visible: !loadingComments() && comments().length == 0">no comments found</p>
				<!-- ko foreach: comments -->
				<h3><a data-bind="text: title, attr: {href: url}"></a></h3>
				<section class=comment>
					<div class=userinfo>
						<!-- ko if: username -->
						<div class=username data-bind="css: {friend: friend}, attr: {title: friend ? (displayname || username) + ' is your friend' : null}">
							<a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a>
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
						<div class="content edit" data-bind="visible: editing">
							<textarea data-bind="value: markdown"></textarea>
						</div>
						<footer data-bind="visible: canchange">
							<a class="okay action" data-bind="visible: editing(), click: $parent.SaveComment" href="/comments.php?ajax=save">save</a>
							<a class="cancel action" data-bind="visible: editing(), click: $parent.UneditComment" href="#">cancel</a>
							<a class="edit action" data-bind="visible: !editing(), click: $parent.EditComment" href="/comments.php?ajax=edit">edit</a>
							<a class="del action" data-bind="visible: !editing(), click: $parent.DeleteComment" href="/comments.php?ajax=delete">delete</a>
						</footer>
					</div>
				</section>

				<!-- /ko -->
				<p class=loading data-bind="visible: loadingComments">loading comments . . .</p>
				<p class=calltoaction data-bind="visible: hasMoreComments, click: LoadComments"><a class="get action" href="?ajax=getall">load more comments</a></p>
			</div>
<?php
$html->Close();

/**
 * Find the user whose comments are being displayed (if not all users).  Will
 * redirect to the list of users if unable to find the user.
 * @param string $_GET['username'] username to display comments from (unset for all users)
 * @return object user object with id, username, displayname, and avatar
 */
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

/**
 * Verify the type and key fields, which are needed to know what the comments
 * apply to.
 * @param t7ajax $ajax Ajax object for potential error message
 * @param array $req Request array to check for type and key; $_GET or $_POST
 * @param string $field2 Second field to require; key for generic or id for specific
 * @return boolean true if verified successfully
 */
function VerifyCommonFields($ajax, $req, $field2 = 'key') {
	if(isset($req['type']))
		switch($req['type']) {
			case 'blog':
			case 'guide':
			case 'photos':
			case 'art':
			case 'lego':
			case 'stories':
			case 'code_vs':
			case 'code_web':
			case 'update':
				if(isset($req[$field2]))
					return true;
				else
					$ajax->Fail($field2 . ' is required');
				break;
			default:
				$ajax->Fail('invalid comment type specified.  valid types are:  blog, guide, photos, art, lego, stories, code_vs, code_web, update.');
				break;
	}
	else
		$ajax->Fail('comment type is required');
		return false;
}

function KeyName($type) {
	switch($type) {
		case 'blog':
			return 'entry';
		case 'photos':
			return 'photo';
		case 'stories':
			return 'story';
		case 'code_vs':
			return 'application';
		case 'code_web':
			return 'script';
		case 'update':
			return 'message';
		default:
			return $type;
	}
	return false;
}

function GetAllComments() {
	global $ajax, $db, $user;
	$userid = +$_GET['userid'];
	$oldest = +$_GET['oldest'] ? +$_GET['oldest'] : time() + 43200;
	if($cs = $db->prepare('select c.srctbl, c.id, c.title, c.url, c.posted, c.author as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, c.authorname, c.authorurl, '
			. 'coalesce(a.markdown, b.markdown, cv.markdown, cw.markdown, g.markdown, l.markdown, p.markdown, s.markdown, uc.markdown) as markdown, coalesce(a.html, b.html, cv.html, cw.html, g.html, l.html, p.html, s.html, uc.html) as html from contributions as c left join users as u on u.id=c.author left join users_friends as f on f.friend=c.author and f.fan=\'' . +$user->ID .
			'\' left join art_comments as a on a.id=c.id and c.srctbl=\'art_comments\' '
			. 'left join blog_comments as b on b.id=c.id and c.srctbl=\'blog_comments\' '
			. 'left join code_vs_comments as cv on cv.id=c.id and c.srctbl=\'code_vs_comments\' '
			. 'left join code_web_comments as cw on cw.id=c.id and c.srctbl=\'code_web_comments\' '
			. 'left join guide_comments as g on g.id=c.id and c.srctbl=\'guide_comments\' '
			. 'left join lego_comments as l on l.id=c.id and c.srctbl=\'lego_comments\' '
			. 'left join photos_comments as p on p.id=c.id and c.srctbl=\'photos_comments\' '
			. 'left join stories_comments as s on s.id=c.id and c.srctbl=\'stories_comments\' '
			. 'left join update_comments as uc on uc.id=c.id and c.srctbl=\'update_comments\' where c.conttype=\'comment\' and c.posted<? and (c.author=? or ?=0) order by posted desc limit ' . MAX_COMMENT_GET))
		if($cs->bind_param('iii', $oldest, $userid, $userid))
			if($cs->execute())
				if($cs->bind_result($srctbl, $id, $title, $url, $posted, $canchange, $username, $displayname, $avatar, $level, $friend, $authorname, $authorurl, $markdown, $html)) {
					$ajax->Data->comments = [];
					$ajax->Data->oldest = 0;
					while($cs->fetch()) {
						$ajax->Data->oldest = +$posted;
						$c = [
							'srctbl' => $srctbl,
							'id' => +$id,
							'title' => $title,
							'url' => $url,
							'posted' => t7format::TimeTag('g:i a \o\n l F jS Y', $posted),
							'canchange' => $user->IsLoggedIn() && ($canchange == $user->ID && $markdown || $user->IsAdmin()),
							'username' => $username,
							'displayname' => $displayname,
							'avatar' => $avatar === '' ? t7user::DEFAULT_AVATAR : $avatar,
							'level' => $level,
							'friend' => $friend,
							'name' => !$username && $authorname == '' ? t7user::DEFAULT_NAME : $authorname,
							'contacturl' => !$user->IsLoggedIn() && substr($authorurl, 0, 7) == 'mailto:' ? '' : $authorurl,
							'html' => $html
						];
						if($c['canchange'])
							$c['markdown'] = !$markdown && $user->IsAdmin() ? $html : $markdown;
						$ajax->Data->comments[] = $c;
					}
					if($more = $db->query('select count(1) as num from contributions where conttype=\'comment\' and posted<\'' . +$ajax->Data->oldest . '\' and (author=\'' . +$userid . '\' or \'' . +$userid . '\'=0)'))
						if($more = $more->fetch_object())
							$ajax->Data->more = +$more->num;
				} else
					$ajax->Fail('error binding results from comment lookup:  ' . $cs->error);
			else
				$ajax->Fail('error executing comment lookup:  ' . $cs->error);
		else
			$ajax->Fail('error binding parameters to look up comments:  ' . $cs->error);
	else
		$ajax->Fail('error preparing to look up comments:  ' . $db->error);
}

function GetComments() {
	global $ajax, $db, $user;
	if(VerifyCommonFields($ajax, $_GET))
		if($comments = $db->query('(select c.id, c.posted, c.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, c.name, c.contacturl, c.markdown, c.html from ' . $_GET['type'] . '_comments as c left join users as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=\'' . +$user->ID . '\' where c.' . KeyName($_GET['type']) . '=\'' . $db->escape_string($_GET['key']) . '\' and c.posted<\'' . (+$_GET['oldest'] ? +$_GET['oldest'] : time() + 43200) . '\' order by c.posted desc limit ' . MAX_COMMENT_GET . ') order by posted')) {
			$ajax->Data->comments = [];
			$ajax->Data->oldest = 0;
			while($comment = $comments->fetch_object()) {
				$comment->id += 0;
				$ajax->Data->oldest = +$comment->posted;
				$comment->posted = t7format::TimeTag('g:i a \o\n l F jS Y', $comment->posted);
				if(!$user->IsLoggedIn() && substr($comment->contacturl, 0, 7) == 'mailto:')
					$comment->contacturl = '';
				$comment->canchange = $user->IsLoggedIn() && ($comment->canchange == $user->ID && $comment->markdown || $user->IsAdmin());
				if(!$comment->canchange)
					unset($comment->markdown);
				elseif(!$comment->markdown && $user->IsLoggedIn())
					$comment->markdown = $comment->html;
				if($comment->avatar === '')
					$comment->avatar = t7user::DEFAULT_AVATAR;
				$ajax->Data->comments[] = $comment;
			}
			if($more = $db->query('select count(1) as num from ' . $_GET['type'] . '_comments where ' . KeyName($_GET['type']) . '=\'' . $db->escape_string($_GET['key']) . '\' and posted<\'' . +$ajax->Data->oldest . '\''))
				if($more = $more->fetch_object())
					$ajax->Data->more = +$more->num;
		} else
			$ajax->Fail('error getting comments.');
}
