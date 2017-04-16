<?php
define('MAXITEMS', 16);
define('FORUM_POSTS_PER_PAGE', 20);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 unifeed', '/', 'all track7 activity', 'copyright 2006 - 2017 track7');

if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
	while($act = $acts->fetch_object()) {
		if($act->hasmore)
			$act->preview .= '<p><a href="' . htmlspecialchars($act->url) . '">⇨ read more</a></p>';
		$rss->AddItem($act->preview, ContributionPrefix($act->conttype) . $act->title . ContributionPostfix($act->conttype) . ' by ' . AuthorName($act), $act->url, $act->posted, $act->url, true);
	}

$rss->End();

function ContributionPrefix($type) {
	switch($type) {
		case 'comment':
			return 'comment on ';
	}
	return '';
}

function ContributionPostfix($type) {
	switch($type) {
		case 'discuss':
			return ' discussion';
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
