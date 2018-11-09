<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['threadid']))
	if($d = $db->query('select id from forum_discussions where threadid=\'' . +$_GET['threadid'] . '\' limit 1'))
		if($d = $d->fetch_object()) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $d->id));
			die;
		}
header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
