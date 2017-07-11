<?php
define('MAXITEMS', 16);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 unifeed', '/', 'all track7 activity', 'copyright 2006 - 2017 track7');

if($acts = t7contrib::GetAll(false, MAXITEMS))
	while($act = $acts->fetch_object()) {
		if($act->hasmore)
			$act->preview .= '<p><a href="' . htmlspecialchars($act->url) . '">⇨ read more</a></p>';
		$rss->AddItem($act->preview, t7contrib::Prefix($act->conttype) . $act->title . t7contrib::Postfix($act->conttype) . ' by ' . AuthorName($act), $act->url, $act->posted, $act->url, true);
	}

$rss->End();

function AuthorName($act) {
	if($act->displayname)
		return $act->displayname;
	if($act->username)
		return $act->username;
	if($act->authorname)
		return $act->authorname;
	return 'random internet person';
}
