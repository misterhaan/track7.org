<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$lego = false;
if(isset($_GET['model']) && $lego = $db->query('select l.id, l.url, l.title, l.pieces, l.mans, l.posted, l.rating, l.votes, l.deschtml, v.vote from lego_models as l left join lego_votes as v on v.lego=l.id and ' . ($user->IsLoggedIn() ? 'voter=\'' . +$user->ID . '\' ' : 'ip=inet_aton(\'' . $db->escape_string($_SERVER['REMOTE_ADDR']) . '\') ') . 'where l.url=\'' . $db->escape_string($_GET['model']) . '\' limit 1'))
	$lego = $lego->fetch_object();

if(!$lego) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('not found - lego models');
?>
			<h1>404 lego model not found</h1>

			<p>
				sorry, we don’t seem to have a lego model by that name.  try picking one
				from <a href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

$prev = $next = false;
if($prev = $db->query('select url, title from lego_models where posted<\'' . +$lego->posted . '\' order by posted desc limit 1'))
	$prev = $prev->fetch_object();
if($next = $db->query('select url, title from lego_models where posted>\'' . +$lego->posted . '\' order by posted limit 1'))
	$next = $next->fetch_object();

$html = new t7html(['vue' => true]);
$html->Open(htmlspecialchars($lego->title) . ' - lego models');
?>
			<h1><?=htmlspecialchars($lego->title); ?></h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=edit href="<?=dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . $lego->id; ?>">edit this lego model</a></nav>
<?php
}
PrevNext($prev, $next);
$lego->posted = t7format::TimeTag('smart', $lego->posted, 'g:i a \o\n l F jS Y');
?>
			<p><img class=lego src="<?=dirname($_SERVER['SCRIPT_NAME']) . '/data/' . $lego->url . '.png'; ?>"></p>
			<p class="image meta">
				<time class=posted datetime="<?=$lego->posted->datetime; ?>" title="posted <?=$lego->posted->title; ?>"><?=$lego->posted->display; ?></time>
				<span class=pieces><?=$lego->pieces; ?> pieces</span>
				<span class=rating data-stars=<?=round($lego->rating*2)/2; ?> title="rated <?=$lego->rating; ?> stars by <?=$lego->votes == 0 ? 'nobody' : ($lego->votes == 1 ? '1 person' : $lego->votes . ' people'); ?>"></span>
			</p>
			<p class="actions image">
				<a class=pdf href="data/<?=$lego->url; ?>.pdf">step-by-step instructions</a>
				<a class=download download href="data/<?=$lego->url; ?>.ldr">ldraw data</a>
			</p>
<?php
echo $lego->deschtml;
?>
			<p>
				how do you like it?  <?php $html->ShowVote('lego', $lego->id, $lego->vote); ?>
			</p>
<?php
PrevNext($prev, $next);
$html->ShowComments('lego model', 'lego', $lego->id);
$html->Close();

function PrevNext($prev, $next) {
?>
			<nav class=tagprevnext>
<?php
	if($next) {
?>
				<a class=prev title="see the lego model posted after this" href="<?=dirname($_SERVER['SCRIPT_NAME']) . '/' . $next->url; ?>"><?=htmlspecialchars($next->title); ?></a>
<?php
	}
?>
				<a class=gallery title="see all lego models" href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
<?php
	if($prev) {
?>
				<a class=next title="see the lego model posted before this" href="<?=dirname($_SERVER['SCRIPT_NAME']) . '/' . $prev->url; ?>"><?=htmlspecialchars($prev->title); ?></a>
<?php
	}
?>
			</nav>
<?php
}
