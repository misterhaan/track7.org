<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if($series = $db->query('select id, url, title, deschtml from stories_series where url=\'' . $db->escape_string($_GET['series']) . '\''))
	if($series = $series->fetch_object()) {
		$html = new t7html(['vue' => true]);
		$html->Open(htmlspecialchars($series->title) . ' - stories');
?>
			<h1 data-series-id=<?=+$series->id; ?>><?=htmlspecialchars($series->title); ?></h1>
<?=$series->deschtml; ?>

			<section id=serieslist>
				<article v-for="story in stories">
					<h2><a :href=story.url>{{story.title}}</a></h2>
					<p class=postmeta v-if=story.posted>
						<span>posted <time :datetime=story.posted.datetime :title=story.posted.title>{{story.posted.display}}</time></span>
					</p>
					<div class=description v-html=story.deschtml></div>
				</article>
			</section>
<?php
	} else {
		header('HTTP/1.0 404 Not Found');
		$html = new t7html([]);
		$html->Open('series not found');
?>
			<h1>404 series not found</h1>
			<p>
				sorry, we don’t seem to have a series by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all stories</a>.
			</p>
<?php
	}
else {
	$html = new t7html([]);
	$html->Open('error - stories');
?>
			<h1>database error</h1>
			<p class=error>database error looking up series information<?=$user->IsAdmin() ? ':  ' . $db->errno . ' ' . $db->error : '.'; ?></p>
<?php
}
$html->Close();
