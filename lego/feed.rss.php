<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$rss = new t7feed('track7 lego models', '/lego/', 'all lego models posted at track7', 'copyright 2005 - 2017 track7');
$legos = 'select url, posted, title, deschtml from lego_models order by posted desc limit ' . t7feed::MAX_RESULTS;
if($legos = $db->query($legos))
	while($lego = $legos->fetch_object())
		$rss->AddItem('<p><img class=art src="/lego/data/' . $lego->url . '.png"></p>' . $lego->deschtml, $lego->title, '/lego/' . $lego->url, $lego->posted, '/lego/' . $lego->url, true);
$rss->End();
