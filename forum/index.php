<?php
define('MAX_THREADS', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'list': GetDiscussions(); break;
	}
	$ajax->Send();
	die;
}

$html = false;
$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from forum_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1')) {
	$tag = $tag->fetch_object();
	$tag->name = htmlspecialchars($tag->name);
}
if($tag) {
	$html = new t7html(['ko' => true, 'rss' => ['title' => $tag->name . ' discussions', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss?tags=' . $tag->name]]);
	$html->Open($tag->name . ' forum');
?>
			<h1>
				<?php echo $tag->name; ?> forum
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss?tags=<?php echo $tag->name; ?>" title="rss feed of the <?php echo $tag->name; ?> forum"></a>
			</h1>
			<div class=editable id=taginfo data-tagid=<?php echo $tag->id; ?>><?php echo $tag->description; ?></div>
<?php
	if($user->IsAdmin()) {
?>
			<label class=multiline id=editdesc>
				<span class=field><textarea></textarea></span>
				<span>
					<a href="#save" title="save tag description" class="action okay"></a>
					<a href="#cancel" title="cancel editing" class="action cancel"></a>
				</span>
			</label>
<?php
	}
} else {
	$html = new t7html(['ko' => true, 'rss' => ['title' => 'discussions', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
	$html->Open('forum');
?>
			<h1>
				forum
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of the forum"></a>
			</h1>

			<nav class=tagcloud data-bind="visible: tags().length">
				<header>tags</header>
				<!-- ko foreach: tags -->
				<a data-bind="text: name, attr: { href: name + '/', title: 'discussions tagged ' + name, 'data-count': count }"></a>
				<!-- /ko -->
			</nav>
<?php
}
// TODO:  show link for starting a new discussion
?>
			<div class=floatbgstop><nav class=actions>
				<a class=new href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/start.php">start a new discussion</a>
<?php
if($tag && $user->IsAdmin()) {
?>
				<a href="#tagedit" class=edit>edit tag description</a>
<?php
}
?>
			</nav></div>

			<!-- ko foreach: discussions -->
			<div class=discussion>
				<h2><a data-bind="text: title, attr: {href: '<?php echo dirname($_SERVER['PHP_SELF']); ?>/' + id}"></a></h2>
				<p class=meta>
					<span class=tags data-bind="foreach: tags"><!-- ko if: $index() > 0 -->, <!-- /ko --><a data-bind="text: $data, attr: {href: '<?php echo dirname($_SERVER['PHP_SELF']); ?>/' + encodeURIComponent($data) + '/'}"></a></span>
					<span class=firstpost data-bind="attr: {title: 'started ' + started.title + ' by ' + (startuserdisplay || startusername || startname)}">
						<time data-bind="text: started.display + ' ago', attr: {datetime: started.datetime}"></time>
						by
						<!-- ko if: startusername -->
						<a data-bind="text: startuserdisplay || startusername, attr: {href: '/user/' + startusername + '/'}"></a>
						<!-- /ko -->
						<!-- ko if: !startusername && startcontact -->
						<a data-bind="text: startname, attr: {href: startcontact}"></a>
						<!-- /ko -->
						<!-- ko if: !startusername && !startcontact -->
						<span data-bind="text: startname"></span>
						<!-- /ko -->
					</span>
					<span class=replies data-bind="text: replies, attr: {title: repliesText}"></span>
					<span class=lastpost data-bind="visible: +replies, attr: {title: 'last reply ' + replied.title + ' by ' + (lastuserdisplay || lastusername || lastname)}">
						<time data-bind="text: replied.display + ' ago', attr: {datetime: replied.datetime}"></time>
						by
						<!-- ko if: lastusername -->
						<a data-bind="text: lastuserdisplay || lastusername, attr: {href: '/user/' + lastusername + '/'}"></a>
						<!-- /ko -->
						<!-- ko if: !lastusername && lastcontact -->
						<a data-bind="text: lastname, attr: {href: lastcontact}"></a>
						<!-- /ko -->
						<!-- ko if: !lastusername && !lastcontact -->
						<span data-bind="text: lastname"></span>
						<!-- /ko -->
					</span>
				</p>
			</div>
			<!-- /ko -->

			<p class=loading data-bind="visible: loading">loading discussions . . .</p>

			<p class=calltoaction data-bind="visible: more() && !loading()"><a class="action get" href="?ajax=list" data-bind="click: Load">load more discussions</a></p>
<?php
$html->Close();

function GetDiscussions() {
	global $ajax, $db;
	$before = isset($_GET['before']) && $_GET['before'] ? +$_GET['before'] : time() + 43200;
	$ds = 'select d.id, d.title, group_concat(t.name order by t.name) as tags, fr.posted as started, fu.username as startusername, fu.displayname as startuserdisplay, fr.contacturl as startcontact, fr.name as startname, (select count(1) from forum_replies where discussion=d.id)-1 as replies, lr.posted as replied, lu.username as lastusername, lu.displayname as lastuserdisplay, lr.contacturl as lastcontact, lr.name as lastname from ';
	if(isset($_GET['tagid']) && +$_GET['tagid'])
		$ds .= 'forum_discussion_tags as findtag left join forum_discussions as d on d.id=findtag.discussion ';
	else
		$ds .= 'forum_discussions as d ';
	$ds .= 'left join forum_discussion_tags as dt on dt.discussion=d.id left join forum_tags as t on t.id=dt.tag left join forum_replies as fr on fr.discussion=d.id and fr.posted=(select min(posted) from forum_replies where discussion=d.id) left join users as fu on fu.id=fr.user left join forum_replies as lr on lr.discussion=d.id and lr.posted=(select max(posted) from forum_replies where discussion=d.id) left join users as lu on lu.id=lr.user where lr.posted<\'' . +$before . '\' ';
	if(isset($_GET['tagid']) && +$_GET['tagid'])
		$ds .= 'and findtag.tag=' . +$_GET['tagid'] . ' ';
	$ds .= 'group by d.id order by lr.posted desc limit ' . MAX_THREADS;
	if($ds = $db->query($ds)) {
		$ajax->Data->discussions = [];
		$ajax->Data->latest = 0;
		while($d = $ds->fetch_object()) {
			$ajax->Data->latest = $d->replied;
			$d->started = t7format::TimeTag('ago', $d->started, 'g:i a \o\n l F jS Y');
			$d->tags = explode(',', $d->tags);
			$d->repliesText = +$d->replies > 1 ? +$d->replies . ' replies' : (+$d->replies > 0 ? '1 reply' : 'no replies');
			$d->replied = t7format::TimeTag('ago', $d->replied, 'g:i a \o\n l F jS Y');
			$ajax->Data->discussions[] = $d;
		}
		$ajax->Data->more = '';
		$chk = 'select count(1) as num from forum_discussions as d left join forum_replies as lr on lr.discussion=d.id and lr.posted=(select max(posted) from forum_replies where discussion=d.id) where lr.posted<\'' . +$ajax->Data->latest . '\'';
		if(isset($_GET['tagid']) && +$_GET['tagid'])
			$chk .= ' and \'' . +$_GET['tagid'] . '\' in (select tag from forum_discussion_tags where discussion=d.id)';
		if($chk = $db->query($chk))
			if($chk = $chk->fetch_object())
				$ajax->Data->more = $chk->num;
	} else
		$ajax->Fail('error looking up discussions:  ' . $db->error);
}
