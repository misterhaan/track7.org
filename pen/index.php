<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'stories':
			if($stories = $db->query('select ifnull(ss.lastposted,s.posted) as posted, if(ss.url is null,s.url,concat(ss.url,\'/\')) as url, ifnull(ss.title,s.title) as title, ifnull(ss.deschtml,s.deschtml) as deschtml, ss.numstories from stories as s left join stories_series as ss on ss.id=s.series where s.published=1 group by ifnull(s.series,-s.id) order by posted desc')) {
				$ajax->Data->stories = [];
				while($story = $stories->fetch_object()) {
					if($story->posted > 100)
						$story->posted = t7format::TimeTag('M j, Y', $story->posted, 'g:i a \o\n l F jS Y');
					else
						$story->posted = false;
					$ajax->Data->stories[] = $story;
				}
			} else
				$ajax->Fail('database error looking up stories:  ' . $db->error);
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true]);
$html->Open('stories');
?>
			<h1>
				stories
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of stories"></a>
			</h1>

			<p>
				one of the things i did to pass the time at school was write stories.
				it actually started in a junior high english class where the teacher was
				trying to show us that writing was fun, and i stuck with it until my
				college classes needed more of my focus.  some of the stories here i
				handed in as assignments, including some that are actually essays and
				not stories at all.  one of them is even a poem.  my more recent writing
				has been in the <a href="/bln/">blog</a> but i have a few ideas i might
				try to get out as stories.
			</p>

			<!-- ko foreach: stories -->
			<article data-bind="css: {series: numstories}">
				<h2><a data-bind="text: title, attr: {href: url}"></a></h2>
				<p class=postmeta data-bind="visible: posted || numstories">
					<span data-bind="visible: numstories, text: 'a series of ' + numstories + ' stories'"></span>
					<span data-bind="visible: posted">posted <time data-bind="text: posted.display, attr: {datetime: posted.datetime, title: posted.title}"></time></span>
				</p>
				<div class=description data-bind="html: deschtml"></div>
			</article>
			<!-- /ko -->
<?php
$html->Close();
