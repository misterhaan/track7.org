<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
if($story = $db->query('select s.id, s.published, s.url, ss.url as seriesurl, ss.title as seriestitle, ss.numstories, s.number, s.title, s.storyhtml from stories as s left join stories_series as ss on ss.id=s.series where s.url=\'' . $db->escape_string($_GET['story']) . '\''))
	if($story = $story->fetch_object())
		if($story->published || $user->IsAdmin()) {
			if($story->seriesurl && (!isset($_GET['series']) || $_GET['series'] != $story->seriesurl)) {
				header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $story->seriesurl . '/' . $story->url));
				die;
			}
			if(!$story->seriesurl && isset($_GET['series'])) {
				header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $story->url));
				die;
			}
			$html = new t7html(['ko' => true]);
			if($story->seriesurl) {
				$html->Open(htmlspecialchars($story->title) . ' - ' . htmlspecialchars($story->seriestitle) . ' - stories');
?>
			<h1><?php echo htmlspecialchars($story->title); ?></h1>
			<p class=postmeta>
				story <?php echo +$story->number; ?> of <?php echo +$story->numstories; ?>
				in <a href="."><?php echo $story->seriestitle; ?></a>
			</p>
<?php
			} else {
				$html->Open(htmlspecialchars($story->title) . ' - stories');
?>
			<h1><?php echo htmlspecialchars($story->title); ?></h1>
<?php
			}
			echo $story->storyhtml;
			// TODO:  links to prev / next story and prev / next in series
			$html->ShowComments('story', 'stories', $story->id);
			$html->Close();
		} else
			StoryNotFound();
	else
		StoryNotFound();
else {
	$html = new t7html([]);
	$html->Open('database error - stories');
?>
			<h1>database error</h1>
			<p class=error>a database error got in the way of showing you this story.</p>
<?php
	$html->Close();
}

function StoryNotFound() {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('story not found');
?>
			<h1>404 story not found</h1>
			<p>
				sorry, we don’t seem to have a story by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all stories</a>.
			</p>
<?php
	$html->Close();
}
