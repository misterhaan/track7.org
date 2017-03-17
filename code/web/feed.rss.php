<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 web scripts', '/code/web/', 'web scripts released at track7', 'copyright 2003 - 2017 track7');
$scrs = 'select deschtml, name, released as posted, url from code_web_scripts order by released desc';
if($scrs = $db->query($scrs))
	while($scr = $scrs->fetch_object())
		$rss->AddItem($scr->deschtml, $scr->name, '/code/web/' . $scr->url, $scr->posted, '/code/web/' . $scr->url, true);
$rss->End();