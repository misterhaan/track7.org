<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['tags']) && $_GET['tags']) {
	$rss = new t7feed('track7 froum', '/forum/', 'discussions posted at track7 tagged with ' . $_GET['tags'], 'copyright 2004 - 2017 track7');
	$tags = explode(',', $db->escape_string($_GET['tags']));
	$replies = 'select r.id, r.discussion, r.posted, d.title, u.username, u.displayname, r.name, r.html from forum_discussion_tags as dt right join forum_tags as t on t.id=dt.tag and t.name in (\'' . implode('\', \'', $tags) . '\') left join forum_replies as r on r.discussion=dt.discussion left join forum_discussions as d on d.id=r.discussion left join users as u on u.id=r.user order by r.posted desc limit ' . t7feed::MAX_RESULTS;
} else {
	$rss = new t7feed('track7 forum', '/forum/', 'all discussions posted at track7', 'copyright 2004 - 2017 track7');
	$replies = 'select r.id, r.discussion, r.posted, d.title, u.username, u.displayname, r.name, r.html from forum_replies as r left join forum_discussions as d on d.id=r.discussion left join users as u on u.id=r.user order by r.posted desc limit ' . t7feed::MAX_RESULTS;
}
if($replies = $db->query($replies))
	while($reply = $replies->fetch_object())
		$rss->AddItem($reply->html, $reply->title . ' discussion by ' . ($reply->displayname ? $reply->displayname : $reply->username ? $reply->username : $reply->name), '/forum/' . $reply->discussion . '#r' . $reply->id, $reply->posted, '/forum/' . $reply->discussion . '#r' . $reply->id, true);
$rss->End();
