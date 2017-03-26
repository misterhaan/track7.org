<?php
define('MAXITEMS', 16);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$rss = new t7feed('track7 updates', '/', 'track7 site updates', 'copyright 2006 - 2017 track7');
if($us = $db->query('select id, posted, html from update_messages order by posted desc limit ' . MAXITEMS))
	while($u = $us->fetch_object())
		$rss->AddItem($u->html, 'track7 update', '/updates/' . $u->id, $u->posted, '/updates/' . $u->id, true);
$rss->End();
