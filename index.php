<?php
define('MAXITEMS', 9);
define('LONGDATEFMT', 'g:i a \o\n l F jS Y');
define('FORUM_POSTS_PER_PAGE', 20);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html(['rss' => ['title' => 'unifeed', 'url' => '/feed.rss']]);
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
					<a href="/hb/" title="join or start conversations">
						<img src="/hb/favicon.png" alt="">
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
// get last MAXITEMS from contributions and forum posts
$act = $forum = false;
if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
	$act = $acts->fetch_object();
if($forums = $db->query('select p.id, p.number, p.thread, p.instant as posted, p.subject as title, p.post as preview, u.username, u.displayname from track7_t7data.hbposts as p left join track7_t7data.users as ou on ou.uid=p.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id order by instant desc limit ' . MAXITEMS))
	$forum = $forums->fetch_object();

$items = 0;
while($items < MAXITEMS && ($act || $forum)) {
	if($act && (!$forum || $act->posted > $forum->posted)) {
		ShowContribution($act);
		$act = $acts->fetch_object();
	} elseif($forum) {
		ShowForum($forum);
		$forum = $forums->fetch_object();
	}
	$items++;
}
$html->Close();

function ShowContribution($act) {
?>
			<article class="activity <?php echo $act->conttype; ?>">
				<div class=whatwhen title="<?php echo $act->conttype; ?> at <?php echo t7format::LocalDate(LONGDATEFMT, $act->posted); ?>">
					<time datetime="<?php echo gmdate('c', $act->posted); ?>"><?php echo t7format::SmartDate($act->posted); ?></time>
				</div>
				<div>
					<h2><?php echo ContributionPrefix($act->conttype); ?><a href="<?php echo $act->url; ?>"><?php echo $act->title; ?></a> by <?php echo AuthorLink($act); ?></h2>
					<div class=summary>
						<?php echo $act->preview; ?>
<?php
	if($act->hasmore) {
?>
						<p class=readmore><a href="<?php echo htmlspecialchars($act->url); ?>">⇨ read more</a></p>
<?php
	}
?>
					</div>
				</div>
			</article>
<?php
}

function ShowForum($forum) {
	$forum->url = '/hb/thread' . $forum->thread . '/';
	if($forum->number - 1 > FORUM_POSTS_PER_PAGE)
		$forum->url .= 'skip=' . floor(($forum->number - 1) / FORUM_POSTS_PER_PAGE) * FORUM_POSTS_PER_PAGE;
	$forum->url .= '#p' . $forum->id;
	$forum->authorurl = false;
	$forum->authorname = 'anonymous';
?>
			<article class="activity forum">
				<div class=whatwhen title="forum post at <?php echo t7format::LocalDate(LONGDATEFMT, $forum->posted); ?>">
					<time datetime="<?php echo gmdate('c', $forum->posted); ?>"><?php echo t7format::SmartDate($forum->posted); ?></time>
				</div>
				<div>
					<h2><a href="<?php echo $forum->url; ?>"><?php echo $forum->title; ?></a> by <?php echo AuthorLink($forum); ?></h2>
					<?php echo $forum->preview; ?>
				</div>
			</article>
<?php
}

function ContributionPrefix($type) {
	switch($type) {
		case 'comment':
			return 'comment on ';
	}
	return '';
}

function AuthorLink($act) {
	if($act->username) {
		if(!$act->displayname)
			$act->displayname = $act->username;
		return '<a href="/user/' . htmlspecialchars($act->username) . '/" title="view ' . htmlspecialchars($act->displayname) . '’s profile">' . htmlspecialchars($act->displayname) . '</a>';
	}
	if($act->authorurl)
		return '<a href="'. htmlspecialchars($act->authorurl) . '">' . htmlspecialchars($act->authorname) . '</a>';
	return htmlspecialchars($act->authorname);
}
