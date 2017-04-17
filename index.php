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
// get last MAXITEMS from contributions
if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
	while($act = $acts->fetch_object()) {
?>
			<article class="activity <?php echo $act->conttype; ?>">
				<div class=whatwhen title="<?php echo $act->conttype; ?> at <?php echo t7format::LocalDate(LONGDATEFMT, $act->posted); ?>">
					<time datetime="<?php echo gmdate('c', $act->posted); ?>"><?php echo t7format::SmartDate($act->posted); ?></time>
				</div>
				<div>
					<h2><?php echo ContributionPrefix($act->conttype); ?><a href="<?php echo $act->url; ?>"><?php echo $act->title; ?></a><?php echo ContributionPostfix($act->conttype); ?> by <?php echo AuthorLink($act); ?></h2>
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

$html->Close();

function ContributionPrefix($type) {
	switch($type) {
		case 'comment':
			return 'comment on ';
	}
	return '';
}

function ContributionPostfix($type) {
	switch($type) {
		case 'discuss':
			return ' discussion';
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
