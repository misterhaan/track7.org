<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$tag = false;
if(isset($_GET['tag']))
	if($tag = $db->query('select id, name from art_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
		if($tag = $tag->fetch_object())
			;  // got a tag
		else {  // tag not found, so try getting to the entry without the tag
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['name']));
			die;
		}

$art = false;

if(isset($_GET['art']) && $art = $db->query('select a.id, a.url, i.ext, a.title, a.posted, a.rating, a.votes, a.deschtml, v.vote from art as a left join image_formats as i on i.id=a.format left join art_votes as v on v.art=a.id and ' . ($user->IsLoggedIn() ? 'voter=\'' . +$user->ID . '\' ' : 'ip=inet_aton(\'' . $db->escape_string($_SERVER['REMOTE_ADDR']) . '\') ') . 'where url=\'' . $db->escape_string($_GET['art']) . '\' limit 1'))
	$art = $art->fetch_object();
if(!$art) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open($tag ? 'not found - ' . $tag->name . ' - art' : 'not found - art');
?>
			<h1>404 art not found</h1>

			<p>
				sorry, we don’t seem to have art by that name.  try picking one from
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

// TODO:  handle missing / same dates
$prev = $next = false;
if($tag) {
	if($prev = $db->query('select a.url, a.title from art_taglinks as tl left join art as a on a.id=tl.art where tl.tag=\'' . +$tag->id . '\' and a.posted<\'' . +$art->posted . '\' or a.posted=\'' . +$art->posted . '\' and a.id<\'' . +$art->id . '\' order by a.posted desc, a.id desc limit 1'))
		$prev = $prev->fetch_object();
	if($next = $db->query('select a.url, a.title from art_taglinks as tl left join art as a on a.id=tl.art where tl.tag=\'' . +$tag->id . '\' and a.posted>\'' . +$art->posted . '\' or a.posted=\'' . +$art->posted . '\' and a.id>\'' . +$art->id . '\' order by a.posted, a.id limit 1'))
		$next = $next->fetch_object();
} else {
	if($prev = $db->query('select url, title from art where posted<\'' . +$art->posted . '\' or posted=\'' . +$art->posted . '\' and id<\'' . +$art->id . '\' order by posted desc, id desc limit 1'))
		$prev = $prev->fetch_object();
	if($next = $db->query('select url, title from art where posted>\'' . +$art->posted . '\' or posted=\'' . +$art->posted . '\' and id>\'' . +$art->id . '\' order by posted, id limit 1'))
		$next = $next->fetch_object();
}

$html = new t7html(['ko' => true]);
$html->Open(htmlspecialchars($art->title) . ($tag ? ' - ' . $tag->name . ' - art' : ' - art'));
?>
			<h1><?php echo htmlspecialchars($art->title); ?></h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=edit href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . $art->id; ?>">edit this art</a></nav>
<?php
}
TagPrevNext($prev, $tag, $next);
?>
			<p><img class=art src="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/img/' . $art->url . '.' . $art->ext; ?>"></p>
			<p class=photometa>
<?php
if(+$art->posted) {
	$art->posted = t7format::TimeTag('smart', $art->posted, 'g:i a \o\n l F jS Y');
?>
				<time class=posted datetime="<?php echo $art->posted->datetime; ?>" title="posted <?php echo $art->posted->title; ?>"><?php echo $art->posted->display; ?></time>
<?php
}
if($tags = $db->query('select t.name from art_taglinks as tl left join art_tags as t on t.id=tl.tag where tl.art=\'' . +$art->id . '\''))
	if($tagcount = $tags->num_rows) {
?>
				<span class=tags>
<?php
		while($t = $tags->fetch_object()) {
?>
					<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/' . $t->name; ?>/"><?php echo $t->name; ?></a><?php if(--$tagcount) echo ','; ?>
<?php
		}
?>
				</span>
<?php
	}
?>
				<span class=rating data-stars=<?php echo round($art->rating*2)/2; ?> title="rated <?php echo $art->rating; ?> stars by <?php echo $art->votes == 0 ? 'nobody' : ($art->votes == 1 ? '1 person' : $art->votes . ' people'); ?>"></span>
			</p>
<?php
echo $art->deschtml;
?>
			<p>
				how do you like it?  <?php $html->ShowVote('art', $art->id, $art->vote); ?>
			</p>
<?php
TagPrevNext($prev, $tag, $next);
$html->ShowComments('art', 'art', $art->id);
$html->Close();

function TagPrevNext($prev, $tag, $next) {
?>
			<nav class=tagprevnext>
<?php
	if($next) {
?>
				<a class=prev title="see the art posted after this<?php if($tag) echo ' in ' . $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . ($tag ? '/' . $tag->name . '/' : '/') . $next->url; ?>"><?php echo htmlspecialchars($next->title); ?></a>
<?php
	}
	if($tag) {
?>
				<a class=tag title="see all art posted in <?php echo $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/' . $tag->name . '/'; ?>"><?php echo $tag->name; ?></a>
<?php
	} else {
?>
				<a class=gallery title="see all art" href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
<?php
	}
	if($prev) {
?>
				<a class=next title="see the art posted before this<?php if($tag) echo ' in ' . $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . ($tag ? '/' . $tag->name . '/' : '/') . $prev->url; ?>"><?php echo htmlspecialchars($prev->title); ?></a>
<?php
	}
?>
			</nav>
<?php
}
