<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('game worlds');
?>
			<h1>game worlds</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=new href="editwld.php">add a world</a></nav>
<?php
}
?>
			<p>
				when i was in high school i discovered zzt (and later megazeux) and made
				some game worlds for them.  mostly i named them weirdland with a
				subtitle specific to that game, even though none of them really have
				anything to do with each other.  i finished three of them and then
				started two more.  to play them you will need the game environment,
				which back when i had them were available free as shareware.
			</p>
<?php
if($wlds = $db->query('select w.id, w.url, w.name, w.released, e.name as engine, w.deschtml, w.dmzx from code_game_worlds as w left join code_game_engines as e on e.id=w.engine order by w.released asc')) {
?>
			<nav id=gamewlds>
<?php
	while($wld = $wlds->fetch_object()) {
		$wld->released = t7format::TimeTag('smart', $wld->released, t7format::DATE_LONG);
?>
				<article id="<?=$wld->url; ?>">
					<header>
						<h2>
							<?=htmlspecialchars($wld->name); ?>
<?php
		if($user->IsAdmin()) {
?>
							<a class="edit action" href="editwld.php?id=<?=$wld->id; ?>" title="edit this game world"></a>
<?php
		}
?>
						</h2>
						<p class=meta>
							<time class=posted title="released <?=$wld->released->title; ?>" datetime="<?=$wld->released->datetime; ?>"><?=$wld->released->display; ?></time>
							<span class=gameengine><?=$wld->engine; ?></span>
						</p>
					</header>
					<img class=screenshot alt="" src="files/<?=$wld->url; ?>.png">
					<?=$wld->deschtml; ?>
					<p class=downloads>
						<a class="zip action" href="files/<?=$wld->url; ?>.zip"><?=$wld->url; ?>.zip</a>
<?php
		if($wld->dmzx) {
?>
						<a class="dmzx action" href="http://vault.digitalmzx.net/show.php?id=<?=$wld->dmzx; ?>"><?=htmlspecialchars($wld->name); ?> in the dmzx vault</a>
<?php
		}
?>
					</p>
				</article>
<?php
	}
?>
			</nav>
<?php
}
$html->Close();
