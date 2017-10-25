<?php
define('MAX_ART', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'art':
			if(isset($_GET['tagid']) && +$_GET['tagid'])
				$artq = 'select a.id, a.url, i.ext, a.posted from art_taglinks as t left join art as a on a.id=t.art left join image_formats as i on i.id=a.format where t.tag=\'' . +$_GET['tagid'] . '\'';
			else
				$artq = 'select a.id, a.url, i.ext, a.posted from art as a left join image_formats as i on i.id=a.format';
			if(isset($_GET['beforetime']) && $_GET['beforetime'] !== '' && isset($_GET['beforeid']) && $_GET['beforeid'] !== '')
				$artq .= ' where a.posted<\'' . +$_GET['beforetime'] . '\' or a.posted=\'' . +$_GET['beforetime'] . '\' and a.id<\'' . +$_GET['beforeid'] . '\'';
			$artq .= ' order by a.posted desc, a.id desc limit ' . MAX_ART;
			$ajax->Data->art = [];
			if($art = $db->query($artq))
				while($a = $art->fetch_object()) {
					$posted = t7format::TimeTag('M j, Y', $a->posted, 'g:i a \o\n l F jS Y');
					$posted->timestamp = $a->posted;
					$a->posted = $posted;
					$ajax->Data->art[] = $a;
				}
			$ajax->Data->hasMore = false;
			if($more = $db->query($artq . ', 1'))
				$ajax->Data->hasMore = $more->num_rows > 0;
			break;
	}
	$ajax->Send();
	die;
}

$html = false;
$tag = false;
if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from art_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
	$tag = $tag->fetch_object();
if($tag) {
	$html = new t7html(['ko' => true, 'bodytype' => 'gallery', 'rss' => ['title' => $tag->name . ' art', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss?tags=' . $tag->name]]);
	$html->Open($tag->name . ' - art');
?>
			<h1>
				visual art — <?php echo $tag->name; ?>
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss?tags=<?php echo $tag->name; ?>" title="rss feed of <?php echo $tag->name; ?> art"></a>
			</h1>
<?php
	ShowActions($tag->id);
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
			<p>go back to <a href="/art/">all art</a>.</p>
<?php
} else {
	$html = new t7html(['ko' => true, 'bodytype' => 'gallery', 'rss' => ['title' => 'art', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
	$html->Open('art');
?>
			<h1>
				visual art
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of art"></a>
			</h1>

			<nav class=tagcloud data-bind="visible: tags().length">
				<header>tags</header>
				<!-- ko foreach: tags -->
				<a data-bind="text: name, attr: { href: name + '/', title: 'art tagged ' + name, 'data-count': count }"></a>
				<!-- /ko -->
			</nav>

<?php
	ShowActions();
}
?>
			<ul class=errors data-bind="visible: errors().length, foreach: errors">
				<li data-bind="text: $data"></li>
			</ul>

			<p data-bind="visible: !art().length && !loadingArt()">
				this gallery is empty!
			</p>

			<ol id=artgallery class=gallery data-bind="foreach: art">
				<li>
					<a class="art thumb" data-bind="attr: {href: url}">
						<img data-bind="attr: {src: '/art/img/' + url + '-prev.' + ext}">
						<!-- TODO:  show rating and post date -->
					</a>
				</li>
			</ol>

			<p class=loading data-bind="visible: loadingArt">loading more art . . .</p>
			<p class="more calltoaction" data-bind="visible: hasMoreArt"><a class="action get" href=#nextpage data-bind="click: LoadArt">load more art</a></p>
<?php
$html->Close();

/**
 * create the menu of actions.
 * @param integer $tagid id of the tag to edit from this page, if any
 */
function ShowActions($tagid = false) {
	global $user;
	if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions>
				<a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add art</a>
<?php
		if($tagid) {
?>
				<a href="#tagedit" class=edit>edit tag description</a>
<?php
		}
?>
			</nav></div>

<?php
	}
}
