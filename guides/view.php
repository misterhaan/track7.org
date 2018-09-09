<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']))
	if($tag = $db->query('select name from guide_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
		if($tag = $tag->fetch_object())
			$tag = $tag->name;
		else {  // tag not found, so try getting to the guide without the tag
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['url'] . '/' . $_GET['page']));
			die;
		}

$guide = false;

if(isset($_GET['url']) && $guide = $db->query('select g.id, g.url, g.title, g.status, g.posted, g.updated, g.summary, g.level, g.rating, g.votes, g.views, g.author, v.vote from guides as g left join guide_votes as v on g.id=v.guide and v.' . ($user->IsLoggedIn() ? 'voter=' . +$user->ID . ' and v.ip=0' : 'voter=0 and v.ip=inet_aton(\'' . $_SERVER['REMOTE_ADDR'] . '\')') . ' where g.url=\'' . $db->escape_string($_GET['url']) . '\' limit 1'))
	$guide = $guide->fetch_object();
if(!$guide || $guide->status != 'published' && !$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open($tag ? 'guide not found - ' . $tag . ' - guides' : 'guide not found - guides');
?>
			<h1>404 guide not found</h1>

			<p>
				sorry, we don’t seem to have a guide by that name.  try the list of
				<a href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">all guides</a>.
			</p>
<?php
	$html->Close();
	die;
}
$pages = [];
if($pageinfo = $db->query('select id, number, heading, html from guide_pages where guide=\'' . +$guide->id . '\' order by number'))
	while($p = $pageinfo->fetch_object())
		$pages[$p->number] = $p;

$html = new t7html(['vue' => true]);
$html->Open(htmlspecialchars($guide->title) . ($tag ? ' - ' . $tag . ' - guides' : ' - guides'));
if($guide->status == 'published')
	$db->real_query('update guides set views=views+1 where id=' . +$guide->id);
$tags = [];
if($ts = $db->query('select t.name from guide_taglinks as l left join guide_tags as t on t.id=l.tag where l.guide=' . +$guide->id . ' order by t.name'))
	while($t = $ts->fetch_object())
		$tags[] = '<a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/' . $t->name . '/" title="guides tagged ' . $t->name . '">' . $t->name . '</a>';
?>
			<h1><?=htmlspecialchars($guide->title); ?></h1>
			<p class=guidemeta>
				<span class=guidelevel title="<?=$guide->level; ?> level"><?=$guide->level; ?></span>
				<span class=tags><?=implode(', ', $tags); ?></span>
				<span class=views title="viewed <?=$guide->views; ?> times"><?=$guide->views; ?></span>
				<span class=rating data-stars=<?=round($guide->rating*2)/2; ?> title="rated <?=$guide->rating; ?> stars by <?=$guide->votes == 0 ? 'nobody' : ($guide->votes == 1 ? '1 person' : $guide->votes . ' people'); ?>"></span>
				<time class=posted datetime="<?=gmdate('c', $guide->updated); ?>" title="posted <?=$guide->updated == $guide->posted ? strtolower(date('g:i a \o\n l F jS Y', $guide->updated)) : strtolower(date('g:i a \o\n l F jS Y', $guide->updated)) . ' (originally ' . strtolower(date('g:i a \o\n l F jS Y', $guide->posted)) . ')'; ?>"><?=t7format::SmartDate($guide->updated) ; ?></time>
				<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
			</p>
<?php
if($user->IsAdmin() || $guide->author == $user->ID) {
?>
			<nav class=actions data-id=<?=$guide->id; ?>>
				<a class=edit href="<?=dirname($_SERVER['PHP_SELF']); ?>/edit.php?id=<?=$guide->id; ?>">edit this guide</a>
<?php
	if($user->IsAdmin() && $guide->status == 'draft') {
?>
				<a class=publish href="/api/guides/publish">publish this guide</a>
				<a class=del href="/api/guides/delete">delete this guide</a>
<?php
	}
?>
			</nav>
<?php
}
?>
			<nav class=toc>
				<header>chapters</header>
				<ol>
<?php
foreach($pages as $page) {
?>
					<li><a href="#ch<?=$page->number; ?>" title="jump to chapter:  <?=$page->heading; ?>"><?=$page->heading; ?></a></li>
<?php
}
?>
				</ol>
			</nav>
<?php
echo $guide->summary;
foreach($pages as $page) {
?>
			<h2 id=ch<?=$page->number; ?>><?=$page->heading; ?></h2>
<?php
	echo $page->html;
}

if($guide->status == 'published') {
?>
			<p>
				how was it?  <?=$html->ShowVote('guide', $guide->id, $guide->vote); ?>
			</p>
<?php
	$html->ShowComments('guide', 'guide', $guide->id);
}
$html->Close();

function MakeTOC($pages) {
	$ret = '<nav class=toc><header>chapters</header><ol>';
	foreach($pages as $page)
		$ret .= '<li><a href="#ch' . $page->number . '" title="jump to chapter:  ' . $page->heading . '">' . $page->heading . '</a></li>';
	return $ret . '</ol></nav>';
}
