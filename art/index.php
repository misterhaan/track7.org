<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from art_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
	$tag = $tag->fetch_object();

$html = OpenPage($tag);
if(!$tag)
	$html->ShowTags('art', 'art');
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions>
				<a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add art</a>
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
			<div id=taginfo data-tagid=<?=$tag->id; ?>>
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
				<div class=editable><?=$tag->description; ?></div>
			</div>
			<p>go back to <a href="/art/">all art</a>.</p>
<?php
}
?>
			<section id=visualart>
				<p class=error v-if=error>{{error}}</p>
				<p v-if="!arts.length && !loading">this gallery is empty!</p>

				<ol id=artgallery class=gallery>
					<li v-for="art in arts">
						<a class="art thumb" :href=art.url>
							<img :src="'/art/img/' + art.url + '-prev.' + art.ext">
							<!-- TODO:  show rating and post date -->
						</a>
					</li>
				</ol>

				<p class=loading v-if=loading>loading more art . . .</p>
				<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more art</a></p>
			</section>

<?php
$html->Close();

/**
 * creates and opens the page.
 * @param object $tag tag object with name property, or false if not limited by tag
 * @return t7html
 */
function OpenPage($tag) {
	$feedtitle = $tag ? $tag->name . ' art' : 'art';
	$feedurl = dirname($_SERVER['PHP_SELF']) . '/feed.rss' . ($tag ? '?tags=' . $tag->name : '');
	$pagetitle = $tag ? $tag->name . ' - art' : 'art';
	$headingtext = 'visual art' . ($tag ? ' — ' . $tag->name : '');
	
	$html = new t7html(['vue' => true, 'bodytype' => 'gallery', 'rss' => ['title' => $feedtitle, 'url' => $feedurl]]);
	$html->Open($pagetitle);
	?>
			<h1>
				<?php echo $headingtext; ?>

				<a class=feed href="<?php echo $feedurl ?>" title="rss feed of <?php echo $tag ? $tag->name : 'all'; ?> art"></a>
			</h1>

<?php
	return $html;
}
