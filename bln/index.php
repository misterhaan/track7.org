<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from blog_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
	$tag = $tag->fetch_object();

$html = OpenPage($tag);
if(!$tag)
	$html->ShowTags('blog', 'entries');
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions>
				<a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>start a new entry</a>
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
			<p id=taginfo data-tagtype=blog data-tagid=<?php echo $tag->id; ?>>
				showing blog entries
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
				<span class=editable><?php echo $tag->description; ?></span>
				go back to <a href="/bln/">all entries</a>.
			</p>
<?php
} elseif($user->IsAdmin() && $drafts = $db->query('select url, title from blog_entries where status=\'draft\' order by posted desc'))
	if($drafts->num_rows) {
?>
			<h2>draft entries</h2>
			<ul>
<?php
		while($draft = $drafts->fetch_object()) {
?>
				<li><a href="<?php echo $draft->url; ?>"><?php echo $draft->title; ?></a></li>
<?php
		}
?>
			</ul>
<?php
	}
?>
			<section id=blogentries>
				<ul class=errors v-if=errors.length v-for="er in errors">
					<li>{{er}}</li>
				</ul>

				<p v-if="!loading && !entries.length">
					this blog is empty!
				</p>

				<article v-for="entry in entries">
					<header class=floatbgstop>
						<h2><a :href=entry.url title="view this post with its comments">{{entry.title}}</a></h2>
						<p class=postmeta>
							posted by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a>
							<span class=tags v-if=entry.tags.length>
								in <template v-for="(tag, index) in entry.tags">{{index ? ', ' : ''}}<a class=tag :href="(hasTag ? '../' : '') + tag + '/'" :title="'entries tagged ' + tag">{{tag}}</a></template>
							</span>
							<span class=time v-if="entry.posted.datetime != '1970-01-01T00:00:00+00:00'">
								on <time :datetime=entry.posted.datetime :title=entry.posted.title>{{entry.posted.display}}</time>
							</span>
						</p>
					</header>
					<div class=entrycontent v-html=entry.content>
					</div>
					<footer>
						<p class=readmore>
							<a class=continue :href=entry.url title="read the rest of this entry">continue reading</a>
							<a class=comments :href="entry.url + '#comments'" :title="(entry.comments ? 'join' : 'start') + ' the discussion on this entry'">{{entry.comments}} comments</a>
						</p>
					</footer>
				</article>

				<p class=loading v-if=loading>loading more entries . . .</p>
				<p class="more calltoaction" v-if=hasMore><a class="action get" href=#nextpage v-on:click=Load>load more entries</a></p>
			</section>
<?php
$html->Close();

/**
 * creates and opens the page.
 * @param object $tag tag object with name property, or false if not limited by tag
 * @return t7html
 */
function OpenPage($tag) {
	$feedtitle = $tag ? $tag->name . ' blog entries' : 'blog entries';
	$feedurl = dirname($_SERVER['PHP_SELF']) . '/feed.rss' . ($tag ? '?tags=' . $tag->name : '');
	$pagetitle = $tag ? $tag->name . ' - blog' : 'blog';
	$headingtext = 'latest blog entries' . ($tag ? ' — ' . $tag->name : '');

	$html = new t7html(['vue' => true, 'rss' => ['title' => $feedtitle, 'url' => $feedurl]]);
	$html->Open($pagetitle);
?>
			<h1>
				<?php echo $headingtext; ?>
				<a class=feed href="<?php echo $feedurl ?>" title="rss feed of <?php echo $tag ? $tag->name : 'all'; ?> entries"></a>
			</h1>

<?php
	return $html;
}
