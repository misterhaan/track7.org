<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	if($_GET['ajax'] == 'reply') {
		if(isset($_GET['postid']))
			if($r = $db->query('select id from forum_replies where postid=\'' . +$_GET['postid'] . '\' limit 1'))
				if($r = $r->fetch_object())
					$ajax->Data->id = $r->id;
				else
					$ajax->Fail('no reply with post id ' . +$_GET['postid']);
			else
				$ajax->Fail('error looking up reply from postid:  ' . $db->error);
		else
			$ajax->Fail('post id not specified');
	}
	$ajax->Send();
	die;
}

if(isset($_GET['threadid']))
	if($d = $db->query('select id from forum_discussions where threadid=\'' . +$_GET['threadid'] . '\' limit 1'))
		if($d = $d->fetch_object()) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $d->id));
			die;
		}
header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
