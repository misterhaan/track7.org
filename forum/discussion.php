<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']))
	if($tag = $db->query('select name from forum_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
		if($tag = $tag->fetch_object())
			$tag = $tag->name;
		else {  // tag not found, so try getting to the guide without the tag
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['SCRIPT_NAME']) . '/' . +$_GET['id']));
			die;
		}

$discussion = false;
if(isset($_GET['id']) && $discussion = $db->query('select d.id, d.title, group_concat(t.name order by t.name) as tags from forum_discussions as d left join forum_discussion_tags as dt on dt.discussion=d.id left join forum_tags as t on t.id=dt.tag where d.id=\'' . +$_GET['id'] . '\' group by d.id limit 1'))
	$discussion = $discussion->fetch_object();
if(!$discussion) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open($tag ? 'discussion not found - ' . $tag . ' - forum' : 'discussion not found - forum');
?>
			<h1>404 discussion not found</h1>

			<p>
				sorry, we don’t seem to have a discussion with that id.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all discussions</a>.
			</p>
<?php
	$html->Close();
	die;
}
$discussion->tags = explode(',', $discussion->tags);

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'replies': GetReplies($discussion->id); break;
		case 'addreply': AddReply($discussion->id, $discussion->title); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true]);
$html->Open(htmlspecialchars($discussion->title) . ($tag ? ' - ' . $tag . ' - forum' : ' - forum'));
$taglinks = [];
foreach($discussion->tags as $tag)
	$taglinks[] = '<a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/' . rawurlencode($tag) . '/">' . htmlspecialchars($tag) . '</a>';
?>
			<h1 data-discussion=<?php echo +$discussion->id; ?>><?php echo htmlspecialchars($discussion->title); ?></h1>
			<p class=meta><span class=tags><?php echo implode(', ', $taglinks); ?></span></p>

			<!-- ko foreach: replies -->
			<section class=comment data-bind="attr: {id: 'r' + id}">
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
						<a class="okay action" data-bind="click: $parent.SaveReply" href="replies.php?ajax=update">save</a>
<?php
if($user->IsTrusted()) {
?>
						<a class="okay action" data-bind="click: $parent.StealthSaveReply" href="replies.php?ajax=stealthupdate">stealth save</a>
<?php
}
?>
						<a class="cancel action" data-bind="click: $parent.UneditReply" href="#cancel">cancel</a>
						<!-- /ko -->
						<!-- ko ifnot: editing() -->
						<a class="edit action" data-bind="click: $parent.EditReply" href="#edit">edit</a>
						<a class="del action" data-bind="click: $parent.DeleteReply" href="replies.php?ajax=delete">delete</a>
						<!-- /ko -->
					</footer>
				</div>
			</section>
			<!-- /ko -->

			<h2>add a reply</h2>
			<form id=addreply>
<?php
		if($user->IsLoggedIn()) {
?>
				<label title="you are signed in, so your reply will post with your avatar and a link to your profile">
					<span class=label>name:</span>
					<span class=field><a href="/user/<?php echo $user->Username; ?>/"><?php echo htmlspecialchars($user->DisplayName); ?></a></span>
				</label>
<?php
		} else {
?>
				<label title="please sign in or enter a name so we know what to call you">
					<span class=label>name:</span>
					<span class=field><input id=authorname maxlength=48></span>
				</label>
				<label title="enter a website, web page, or e-mail address if you want people to be able to find you">
					<span class=label>contact:</span>
					<span class=field><input id=authorcontact maxlength=255></span>
				</label>
<?php
		}
?>
				<label class=multiline title="enter your reply using markdown">
					<span class=label>reply:</span>
					<span class=field><textarea id=newreply></textarea></span>
				</label>
				<button id=postreply>post reply</button>
			</form>
<?php
$html->Close();

function GetReplies($id) {
	global $ajax, $db, $user;
	$before = isset($_GET['before']) && $_GET['before'] ? +$_GET['before'] : time() + 43200;
	if($rs = $db->query('select r.id, r.posted, r.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, r.name, r.contacturl, r.markdown, r.html, group_concat(concat(e.posted, \'\t\', eu.username, \'\t\', eu.displayname) order by e.posted separator \'\n\') as edits from forum_replies as r left join users as u on u.id=r.user left join users_friends as f on f.friend=r.user and f.fan=\'' . +$user->ID . '\' left join forum_edits as e on e.reply=r.id left join users as eu on eu.id=e.editor where r.discussion=\'' . +$id . '\' and r.posted<\'' . $before . '\' group by r.id order by r.posted')) {
		$ajax->Data->replies = [];
		while($r = $rs->fetch_object()) {
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
	} else
		$ajax->Fail('error looking up replies:  ' . $db->error);
}

function AddReply($discussion, $title) {
	global $ajax, $db, $user;
	if(isset($_POST['markdown']) && $markdown = trim($_POST['markdown']))
		if($user->IsLoggedIn() || isset($_POST['name']) && isset($_POST['contact']))
			if($ins = $db->prepare('insert into forum_replies (discussion, posted, user, name, contacturl, html, markdown) values (?, ?, ?, ?, ?, ?, ?)')) {
				$posted = time();
				$userid = $user->IsLoggedIn() ? $user->ID : null;
				$name = $user->IsLoggedIn() ? '' : (trim($_POST['name']) ? trim($_POST['name']) : 'random internet person');
				$contact = $user->IsLoggedIn() ? '' : t7format::Link(trim($_POST['contact']));
				$html = t7format::Markdown($markdown);
				if($ins->bind_param('iiissss', $discussion, $posted, $userid, $name, $contact, $html, $markdown))
					if($ins->execute()) {
						$id = $ins->insert_id;
						$ajax->Data->reply = [
							'id' => $id,
							'posted' => t7format::TimeTag('g:i a \o\n l F jS Y', $posted),
							'canchange' => true,
							'username' => $user->IsLoggedIn() ? $user->Username : null,
							'displayname' => $user->IsLoggedIn() ? $user->DisplayName : null,
							'avatar' => $user->IsLoggedIn() ? $user->Avatar : null,
							'level' => $user->IsLoggedIn() ? $user->GetLevelName() : null,
							'friend' => null,
							'name' => $name,
							'contacturl' => $contact,
							'markdown' => $markdown,
							'html' => $html
						];
						if($user->IsLoggedIn())
							$db->real_query('update users_stats as u set u.replies=(select count(1) from forum_replies where user=u.id) where u.id=\'' . +$user->ID . '\'');
						t7send::Tweet(($user->IsLoggedIn() ? $user->DisplayName : $name) . ' discussed ' . $title, t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $discussion . '#r' . $id));
					} else
						$ajax->Fail('error executing query to add reply:  ' . $ins->error);
				else
					$ajax->Fail('error binding parameters to add reply:  ' . $ins->error);
			} else
				$ajax->Fail('error preparing to add reply:  ' . $db->error);
		else
			$ajax->Fail('you’re not logged in but we didn’t ask who you are, which normally means your session expired and you need to log in again');
	else
		$ajax->Fail('can’t add a reply with no message');
}
