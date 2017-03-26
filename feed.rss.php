<?php
define('MAXITEMS', 16);
define('FORUM_POSTS_PER_PAGE', 20);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 unifeed', '/', 'all track7 activity', 'copyright 2006 - 2017 track7');

$act = $forum = false;
if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
	$act = $acts->fetch_object();
if($forums = $db->query('select p.id, p.number, p.thread, p.instant as posted, p.subject as title, p.post as preview, u.username, u.displayname from track7_t7data.hbposts as p left join track7_t7data.users as ou on ou.uid=p.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id order by instant desc limit ' . MAXITEMS))
	$forum = $forums->fetch_object();

$items = 0;
while($items < MAXITEMS && ($act || $forum)) {
	if($act && (!$forum || $act->posted > $forum->posted)) {
		if($act->hasmore)
			$act->preview .= '<p><a href="' . htmlspecialchars($act->url) . '">⇨ read more</a></p>';
		$rss->AddItem($act->preview, ContributionPrefix($act->conttype) . $act->title . ' by ' . AuthorName($act), $act->url, $act->posted, $act->url, true);
		$act = $acts->fetch_object();
	} elseif($forum) {
		$forum->url = '/hb/thread' . $forum->thread . '/';
		if($forum->number - 1 > FORUM_POSTS_PER_PAGE)
			$forum->url .= 'skip=' . floor(($forum->number - 1) / FORUM_POSTS_PER_PAGE) * FORUM_POSTS_PER_PAGE;
		$forum->url .= '#p' . $forum->id;
		$rss->AddItem($forum->preview, $forum->title . ' by ' . AuthorName($forum), $forum->url, $forum->posted, $forum->url, true);
		$forum = $forums->fetch_object();
	}
	$items++;
}

$rss->End();

function ContributionPrefix($type) {
	switch($type) {
		case 'comment':
			return 'comment on ';
	}
	return '';
}

function AuthorName($act) {
	if($act->displayname)
		return $act->displayname;
	if($act->username)
		return $act->username;
	if($act->authorname)
		return $act->authorname;
	return 'anonymous';
}
