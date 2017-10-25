<?php
define('MAX_UPDATE_GET', 16);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'list': ListUpdates(); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true, 'rss' => ['title' => 'updates', 'url' => 'feed.rss']]);
$html->Open('track7 updates');
?>
			<h1>
				track7 updates
				<a class="feed" href="feed.rss" title="rss feed of track7 updates"></a>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions><a class=new href="new.php">add update message</a></nav></div>
<?php
}
?>
			<!-- ko foreach: updates -->
			<article class="activity update">
				<div class=whatwhen data-bind="attr: {title: 'site update at ' + posted.title}">
					<time data-bind="attr: {datetime: posted.datetime}, html: posted.display"></time>
				</div>
				<div>
					<h2></h2>
					<div class=summary data-bind="html: html"></div>
					<p><a data-bind="attr: {href: id}, text: comments == 1 ? '1 comment' : comments + ' comments'"></a></p>
				</div>
			</article>
			<!-- /ko -->
			<nav class="showmore calltoaction" data-bind="visible: hasmore"><a class="action get" href="#loadmore" data-bind="click: Load">load older updates</a></nav>
			<p class=loading data-bind="visible: loading">loading . . .</p>
<?php
$html->Close();

function ListUpdates() {
	global $ajax, $db;
	$oldest = isset($_GET['oldest']) ? +$_GET['oldest'] : 0;
	if(!$oldest)
		$oldest = null;
	$oldid = isset($_GET['oldid']) ? +$_GET['oldid'] : 0;
	if($us = $db->prepare('select u.id, u.posted, u.html, count(c.id) as comments from update_messages as u left join update_comments as c on c.message=u.id where ? is null or u.posted<? or u.posted=? and u.id<? group by u.id order by u.posted desc, u.id desc limit ' . MAX_UPDATE_GET)) {
		if($us->bind_param('iiii', $oldest, $oldest, $oldest, $oldid))
			if($us->execute())
				if($us->bind_result($id, $posted, $html, $comments)) {
					$ajax->Data->updates = [];
					while($us->fetch()) {
						$postdate = t7format::TimeTag('smart', $posted, 'g:i a \o\n l F jS Y');
						$postdate->timestamp = $posted;
						$ajax->Data->updates[] = ['id' => $id, 'posted' => $postdate, 'html' => $html, 'comments' => $comments];
					}
					$us->close();
					if($more = $db->query('select 1 from update_messages where posted<' . +$posted . ' or posted=' . +$posted . ' and id<' . +$id . ' limit 1'))
						$ajax->Data->hasmore = $more->num_rows > 0;
				} else
					$ajax->Fail('error binding results for updates:  ' . $us->error);
			else
				$ajax->Fail('error getting updates:  ' . $us->error);
		else
			$ajax->Fail('error binding paramaters to get updates:  ' . $us->error);
	} else
		$ajax->Fail('error preparing to get updates:  ' . $db->error);
}
