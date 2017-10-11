<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['tags']) && $_GET['tags']) {
	$rss = new t7feed('track7 guides', '/guides/', 'guides posted at track7 tagged with ' . $_GET['tags'], 'copyright 2004 - 2016 track7');
	$tags = explode(',', $db->escape_string($_GET['tags']));
	$guides = 'select g.url, g.updated, g.title, g.summary from guide_taglinks as tl right join guide_tags as t on t.id=tl.tag and t.name in (\'' . implode('\', \'', $tags) . '\') left join guides as g on g.id=tl.guide where g.status=\'published\' order by g.updated desc limit ' . t7feed::MAX_RESULTS;
} else {
	$rss = new t7feed('all track7 guides', '/guides/', 'all guides posted at track7', 'copyright 2004 - 2017 track7');
	$guides = 'select url, updated, title, summary from guides where status=\'published\' order by updated desc limit ' . t7feed::MAX_RESULTS;
}
if($guides = $db->query($guides))
	while($guide = $guides->fetch_object()) {
		$guide->summary = str_replace('href="/', 'href="' . t7format::FullUrl('/'), $guide->summary);
		$rss->AddItem($guide->summary . '<p>» <a href="/guide/' . $guide->url . '">read more...</a></p>', $guide->title, '/guide/' . $guide->url, $guide->updated, '/guide/' . $guide->url, true);
	}
$rss->End();
