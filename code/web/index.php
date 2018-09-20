<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('web scripts');
?>
			<h1>
				web scripts
				<a class=feed href="<?=dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of web scripts"></a>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=new href="editscr.php">add a web script</a></nav>
<?php
}
?>
			<p>
				these web scripts include snippets you can work into your own website,
				userscripts that can customize other websites, and web applications.
				they’re based on web technologies such as html, css, php, mysql, and
				javascript.
			</p>
<?php
if($scrs = $db->query('select s.url, s.name, s.deschtml, s.released, u.name as typename from code_web_scripts as s left join code_web_usetype as u on u.id=s.usetype order by s.released desc')) {
?>
			<nav id=webscripts>
<?php
	while($scr = $scrs->fetch_object()) {
		$scr->released = t7format::TimeTag('smart', $scr->released, t7format::DATE_LONG);
?>
				<article>
					<header>
						<h2><a href="<?=$scr->url; ?>"><?=htmlspecialchars($scr->name); ?></a></h2>
						<p class=meta>
							<span class=scripttype><?=$scr->typename; ?></span>
							<time class=posted title="released <?=$scr->released->title; ?>" datetime="<?=$scr->released->datetime; ?>"><?=$scr->released->display; ?></time>
						</p>
					</header>
					<?=$scr->deschtml; ?>
				</article>
<?php
	}
?>
			</nav>
<?php
}
$html->Close();
