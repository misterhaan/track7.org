<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from photos_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
	$tag = $tag->fetch_object();

$html = OpenPage($tag);
if(!$tag)
	$html->ShowTags('photos', 'photos');
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions>
				<a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add a photo or video</a>
<?php
	if($tag) {
?>
				<a href="#tagedit" class=edit>edit tag description</a>
<?php
	}
?>
			</nav></div>

<?php
}
if($tag) {
?>
			<div id=taginfo data-tagid=<?php echo $tag->id; ?>>
<?php
	if($user->IsAdmin()) {
?>
				<label class=multiline id=editdesc>
					<span class=field><textarea></textarea></span>
					<span>
						<a href="#save" title="save tag description" class="action okay"></a>
						<a href="#cancel" title="cancel editing" class="action cancel"></a>
					</span>
				</label>
<?php
	}
?>
				<div class=editable><?php echo $tag->description; ?></div>
			</div>
			<p>go back to <a href="/album/">all photos</a>.</p>

<?php
}
?>
			<section id=albumphotos>
				<ol id=photogallery class=gallery v-if=photos.length>
					<li v-for="photo in photos">
						<a class="photo thumb" :href=photo.url>
							<img :src="'/album/photos/' + photo.url + '.jpg'">
							<span class=caption>{{photo.caption}}</span>
						</a>
					</li>
				</ol>

				<p class=loading v-if=loading>loading more photos . . .</p>
				<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click=Load>load more photos</a></p>
				<p v-if="!photos.length && !loading">this album is empty!</p>
				<p class=error v-if=error>{{error}}</p>
			</section>
<?php
$html->Close();

/**
 * creates and opens the page.
 * @param object $tag tag object with name property, or false if not limited by tag
 * @return t7html
 */
function OpenPage($tag) {
	$feedtitle = $tag ? $tag->name . ' photos' : 'photos';
	$feedurl = dirname($_SERVER['PHP_SELF']) . '/feed.rss' . ($tag ? '?tags=' . $tag->name : '');
	$pagetitle = $tag ? $tag->name . ' - photo album' : 'photo album';
	$headingtext = 'photo album' . ($tag ? ' — ' . $tag->name : '');

	$html = new t7html(['vue' => true, 'bodytype' => 'gallery', 'rss' => ['title' => $feedtitle, 'url' => $feedurl]]);
	$html->Open($pagetitle);
	?>
			<h1>
				<?php echo $headingtext; ?>

				<a class=feed href="<?php echo $feedurl ?>" title="rss feed of <?php echo $tag ? $tag->name : 'all'; ?> photos"></a>
			</h1>

<?php
	return $html;
}
