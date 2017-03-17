<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$rss = new t7feed('track7 application releases', '/code/vs/', 'applications released at track7', 'copyright 2008 - 2017 track7');
$rels = 'select r.released as posted, r.changelog, a.url, concat(a.name, \' v\', r.major, \'.\', r.minor, \'.\', r.revision) as title from code_vs_releases as r left join code_vs_applications as a on a.id=r.application order by r.released desc limit ' . t7feed::MAX_RESULTS;
if($rels = $db->query($rels))
	while($rel = $rels->fetch_object())
		$rss->AddItem($rel->changelog, $rel->title, '/code/vs/' . $rel->url, $rel->posted, '/code/vs/' . $rel->url, true);
$rss->End();
