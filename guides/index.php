<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from guide_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
	$tag = $tag->fetch_object();

$html = OpenPage($tag);
if(!$tag)
	$html->ShowTags('guide', 'guides');

if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions>
				<a href="<?=dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>start a new guide</a>
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
			<p id=taginfo data-tagtype=guide data-tagid=<?=$tag->id; ?>>
				showing guides dealing with
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
				<span class=editable><?=$tag->description; ?></span>
				go back to <a href="/guides/">all guides</a>.
			</p>
<?php
} elseif($user->IsAdmin() && $drafts = $db->query('select url, title from guides where status=\'draft\' order by posted desc'))
	if($drafts->num_rows) {
?>
			<h2>draft entries</h2>
			<ul>
<?php
		while($draft = $drafts->fetch_object()) {
?>
				<li><a href="<?=$draft->url; ?>"><?=$draft->title; ?></a></li>
<?php
		}
?>
			</ul>
<?php
	}
?>
			<section id=guides>
				<p class=error v-if=error>{{error}}</p>

				<p v-if="!guides.length && !loading">
					no guides!  how will we know what to do?
				</p>

				<article v-for="guide of guides">
					<header class=floatbgstop>
						<h2><a :href=guide.url title="read this guide">{{guide.title}}</a></h2>
						<p class=guidemeta>
							<span class=guidelevel :title="guide.level + ' level'">{{guide.level}}</span>
							<span class=tags v-if=guide.tags.length :title="guide.tags.length == 1 ? '1 tag' : guide.tags.length + ' tags'">
								<template v-for="(tag, index) in guide.tags">{{index ? ', ' : ''}}<a class=tag :href="(hasTag ? '../' : '') + tag + '/'" :title="'guides tagged ' + tag">{{tag}}</a></template>
							</span>
							<span class=views :title="'viewed ' + guide.views + ' times'">{{guide.views}}</span>
							<span class=rating :data-stars=Math.round(guide.rating*2)/2 :title="'rated ' + guide.rating + ' stars by ' + (guide.votes == 0 ? 'nobody' : (guide.votes == 1 ? '1 person' : guide.votes + ' people'))"></span>
							<time class=posted v-html=guide.updated.display  :datetime=guide.updated.datetime :title="'posted ' + (guide.posted ? guide.updated.title + ' (originally ' + guide.posted + ')' : guide.updated.title)"></time>
							<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
						</p>
					</header>
					<div class=summary v-html=guide.summary>
					</div>
					<footer><p class=readmore>
						<a class=continue :href=guide.url>read this guide</a>
					</p></footer>
				</article>

				<p class=loading v-if=loading>loading more guides . . .</p>
				<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more guides</a></p>
			</section>
<?php
$html->Close();

/**
 * creates an opens the page.
 * @param object $tag tag object with name property, or false if not limited by tag
 * @return t7html
 */
function OpenPage($tag) {
	$feedtitle = $tag ? $tag->name . ' guides' : 'guides';
	$feedurl = dirname($_SERVER['PHP_SELF']) . '/feed.rss' . ($tag ? '?tags=' . $tag->name : '');
	$pagetitle = $tag ? $tag->name . ' - guides' : 'guides';
	$headingtext = 'latest guides' . ($tag ? ' — ' . $tag->name : '');

	$html = new t7html(['vue' => true, 'rss' => ['title' => $feedtitle, 'url' => $feedurl]]);
	$html->Open($pagetitle);
	?>
			<h1>
				<?=$headingtext; ?>
				<a class=feed href="<?=$feedurl ?>" title="rss feed of <?=$tag ? $tag->name : 'all'; ?> guides"></a>
			</h1>

<?php
	return $html;
}
