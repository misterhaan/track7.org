<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('applications');
?>
			<h1>
				applications
				<a class=feed href="<?=dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of application releases"></a>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=new href="editapp">add an application</a></nav>
<?php
}
?>
			<p>
				each application is available in a windows installer package, unless
				it’s older than windows installer in which case it’s a zip file with
				a setup.exe and a couple other files.
			</p>
			<p>
				source code for each release is also provided so you can customize and /
				or learn from my work.  newer releases are on github, preceded by
				subversion, and zip files before that.
			</p>
<?php
if($apps = $db->query('select * from (select a.id, a.url, a.name, a.deschtml, r.version, r.released, r.binurl, r.bin32url from code_vs_applications as a join (select * from (select application, concat(major, \'.\', minor, \'.\', revision) as version, released, binurl, bin32url from code_vs_releases order by application, released desc) as rls) as r on r.application=a.id where r.application is not null order by a.id, r.released desc) as ar group by ar.id order by ar.released desc')) {
	$is64 = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'x86_64';
	$is64 = stripos($is64, 'x86_64') !== false || stripos($is64, 'x86-64') !== false || stripos($is64, 'win64') !== false || stripos($is64, 'x64;') !== false || stripos($is64, 'amd64') !== false || stripos($is64, 'wow64') !== false || stripos($is64, 'x64_64') !== false;
?>
			<nav id=vsapps>
<?php
	while($app = $apps->fetch_object()) {
		$app->released = t7format::TimeTag('smart', $app->released, t7format::DATE_LONG);
		if(strpos($app->binurl, '/') === false)
			$app->binurl = 'files/' . $app->binurl;
		if($app->bin32url && strpos($app->bin32url, '/') === false)
			$app->bin32url = 'files/' . $app->bin32url;
?>
				<article>
					<header>
						<h2><a href="<?=$app->url; ?>">
							<img class=icon src="files/<?=$app->url; ?>.png" alt="">
							<?=htmlspecialchars($app->name); ?>
						</a></h2>
						<p class=meta>
							<span class=version title="latest version <?=$app->version; ?>">v<?=$app->version; ?></span>
							<time class=posted title="latest release <?=$app->released->title; ?>" datetime="<?=$app->released->datetime; ?>"><?=$app->released->display; ?></time>
						</p>
					</header>
					<?=$app->deschtml; ?>
					<p class="downloads"><a class="action download" href="<?=$is64 || !$app->bin32url ? $app->binurl : $app->binurl; ?>">download latest release<?php if($app->bin32url) echo ' (' . ($is64 ? '64' : '32') . '-bit)'; ?></a></p>
					<p class="downloads"><a class="action list" href="<?=$app->url; ?>">other versions and source code</a></p>
				</article>
<?php
	}
?>
			</nav>
<?php
}
$html->Close();
