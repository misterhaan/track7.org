<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from forum_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1')) {
	$tag = $tag->fetch_object();
	$tag->name = htmlspecialchars($tag->name);
}

$html = OpenPage($tag);
if($tag) {
?>
			<div id=taginfo data-tagtype=forum data-tagid=<?=$tag->id; ?>>
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
<?php
} else
	$html->ShowTags('forum', 'discussions');
?>
			<div class=floatbgstop><nav class=actions>
				<a class=new href="<?=dirname($_SERVER['PHP_SELF']); ?>/start.php">start a new discussion</a>
<?php
if($tag && $user->IsAdmin()) {
?>
				<a href="#tagedit" class=edit>edit tag description</a>
<?php
}
?>
			</nav></div>

			<div id=discussionlist>
				<div class=discussion v-for="disc in discussions">
					<h2><a :href="'<?=dirname($_SERVER['PHP_SELF']); ?>/' + disc.id">{{disc.title}}</a></h2>
					<p class=meta>
						<span class=tags><template v-for="(tag, index) in disc.tags">{{index ? ', ' : ''}}<a :href="'<?=dirname($_SERVER['PHP_SELF']); ?>/' + encodeURIComponent(tag) + '/'">{{tag}}</a></template></span>
						<span class=firstpost :title="'started ' + disc.started.title + ' by ' + (disc.startuserdisplay || disc.startusername || disc.startname)">
							<time :datetime=disc.started.datetime>{{disc.started.display}} ago</time>
							by
							<a v-if=disc.startusername :href="'/user/' + disc.startusername + '/'">{{disc.startuserdisplay || disc.startusername}}</a>
							<a v-if="!disc.startusername && disc.startcontact" :href=disc.startcontact>{{disc.startname}}</a>
							<span v-if="!disc.startusername && !disc.startcontact">{{disc.startname}}</span>
						</span>
						<span class=replies :title=disc.repliesText>{{disc.replies}}</span>
						<span v-if=disc.replies class=lastpost :title="'last reply ' + disc.replied.title + ' by ' + (disc.lastuserdisplay || disc.lastusername || disc.lastname)">
							<time datetime=disc.replied.datetime>{{disc.replied.display}} ago</time>
							by
							<a v-if=disc.lastusername :href="'/user/' + disc.lastusername + '/'">{{disc.lastuserdisplay || disc.lastusername}}</a>
							<a v-if="!disc.lastusername && disc.lastcontact" :href=disc.lastcontact>{{disc.lastname}}</a>
							<span v-if="!disc.lastusername && !disc.lastcontact">{{disc.lastname}}</span>
						</span>
					</p>
				</div>

				<p class=loading v-if=loading>loading discussions . . .</p>

				<p class=calltoaction v-if="more && !loading"><a class="action get" href="/api/forum/list" v-on:click.prevent=Load>load more discussions</a></p>
			</div>
<?php
$html->Close();

/**
 * creates and opens the page.
 * @param object $tag tag object with name property, or false if not limited by tag
 * @return t7html
 */
function OpenPage($tag) {
	$feedtitle = $tag ? $tag->name . ' discussions' : 'discussions';
	$feedurl = dirname($_SERVER['PHP_SELF']) . '/feed.rss' . ($tag ? '?tags=' . $tag->name : '');
	$pagetitle = $tag ? $tag->name . ' forum' : 'forum';
	$headingtext = $pagetitle;

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
