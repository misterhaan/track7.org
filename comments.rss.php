<?php
define('MAXITEMS', 16);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 comments', '/', 'all track7 comments', 'copyright 2006 - 2017 track7');

if($acts = $db->query('select c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author where c.conttype=\'comment\' order by c.posted desc limit ' . MAXITEMS))
	while($act = $acts->fetch_object()) {
		if($act->hasmore)
			$act->preview .= '<p><a href="' . htmlspecialchars($act->url) . '">⇨ read more</a></p>';
			$rss->AddItem($act->preview, 'comment on ' . $act->title . ' by ' . AuthorName($act), $act->url, $act->posted, $act->url, true);
	}

$rss->End();

function AuthorName($act) {
	if($act->displayname)
		return $act->displayname;
	if($act->username)
		return $act->username;
	if($act->authorname)
		return $act->authorname;
	return 'anonymous';
}
