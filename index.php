<?php
define('MAXITEMS', 9);
define('LONGDATEFMT', 'g:i a \o\n l F jS Y');
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'activity':
			$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;
			if($acts = t7contrib::GetAll($before, MAXITEMS)) {
				$ajax->Data->acts = [];
				$ajax->Data->latest = false;
				while($act = $acts->fetch_object()) {
					$ajax->Data->latest = $act->posted;
					$act->posted = t7format::TimeTag('smart', $act->posted, LONGDATEFMT);
					$act->prefix = t7contrib::Prefix($act->conttype);
					$act->postfix = t7contrib::Postfix($act->conttype);
					$act->hasmore += 0;  // convert to numeric
					$ajax->Data->acts[] = $act;
				}
				$ajax->Data->more = t7contrib::More($ajax->Data->latest);
			} else
				$ajax->Fail('error looking up activity:  ' . $db->error);
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true, 'rss' => ['title' => 'unifeed', 'url' => '/feed.rss']]);
$html->Open('track7');
?>
			<h1><img alt=track7 src="/images/track7.png"></h1>

			<section id=features>
				<nav>
					<a href="/bln/" title="read the blog">
						<img src="/bln/favicon.png" alt="">
						blog
					</a>
					<a href="/album/" title="see my photos">
						<img src="/album/favicon.png" alt="">
						photo album
					</a>
					<a href="/guides/" title="learn how i’ve done things">
						<img src="/guides/favicon.png" alt="">
						guides
					</a>
					<a href="/lego/" title="download instructions for custom lego models">
						<img src="/lego/favicon.png" alt="">
						lego models
					</a>
					<a href="/art/" title="see sketches and digital artwork">
						<img src="/art/favicon.png" alt="">
						visual art
					</a>
					<a href="/pen/" title="read short fiction and a poem">
						<img src="/pen/favicon.png" alt="">
						stories
					</a>
					<a href="/code/" title="download free software with source code">
						<img src="/code/favicon.png" alt="">
						software
					</a>
					<a href="/forum/" title="join or start conversations">
						<img src="/forum/favicon.png" alt="">
						forums
					</a>
<?php
if($user->IsAdmin()) {
?>
					<a href="/tools/" title="administer track7">
						<img src="/favicon.png" alt="">
						tools
					</a>
<?php
}
?>
				</nav>
			</section>
<?php
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions><a class=new href="/updates/new.php">add update message</a></nav></div>
<?php
}
?>
			<div id=latestactivity>
				<!-- ko foreach: activity -->
				<article class="activity" data-bind="css: conttype">
					<div class=whatwhen data-bind="attr: {title: conttype + ' at ' + posted.title}">
						<time data-bind="html: posted.display, attr: {datetime: posted.datetime}"></time>
					</div>
					<div>
						<h2>
							<span data-bind="text: prefix"></span>
							<a data-bind="text: title, attr: {href: url}"></a>
							<span data-bind="text: postfix"></span>
							by
							<!-- ko if: username -->
							<a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/', title: 'view ' + (displayname || username) + '’s profile'}"></a>
							<!-- /ko -->
							<!-- ko if: !username && authorurl -->
							<a data-bind="text: authorname, attr: {href: authorurl}"></a>
							<!-- /ko -->
							<!-- ko if: !username && !authorurl -->
							<span data-bind="text: authorname"></span>
							<!-- /ko -->
						</h2>
						<div class=summary data-bind="html: preview"></div>
						<p class=readmore data-bind="visible: hasmore"><a data-bind="attr: {href: url}">⇨ read more</a></p>
					</div>
				</article>
				<!-- /ko -->
				<p class=loading data-bind="visible: loading">loading activity...</p>
				<p class="more calltoaction" data-bind="visible: more() && !loading()"><a class="action get" href="#activity" data-bind="click: Load">show more activity</a></p>
			</div>
<?php
$html->Close();
