<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('calculator programs');
?>
			<h1>calculator programs</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a class=new href="editprog.php">add a program</a></nav>
<?php
}
?>
			<p>
				i’ve used all of these programs on a ti-86 calculator, and most of them
				on a ti-85.  i don’t think ti makes those anymore and i’m not sure if
				any of their current models can run these programs.  all programs are
				available for download as as zip file that contains an 85_ or 86_ file
				that can be transfered to a calculator with a utility such as connect-85
				or the official texas instruments software, or at least they could back
				in 1998.
			</p>
<?php
if($progs = $db->query('select p.id, p.url, p.name, p.released, s.name as subject, m.name as model, p.deschtml, p.ticalc from code_calc_progs as p left join code_calc_subject as s on s.id=p.subject left join code_calc_model as m on m.id=p.model order by p.released desc')) {
?>
			<nav id=calcprogs>
<?php
	while($prog = $progs->fetch_object()) {
		$prog->released = t7format::TimeTag('smart', $prog->released, 'M j, Y \a\t g:i a');
?>
				<article id="<?=$prog->url; ?>">
					<header>
						<h2>
							<?=htmlspecialchars($prog->name); ?>
<?php
		if($user->IsAdmin()) {
?>
							<a class="edit action" href="editprog.php?id=<?=$prog->id; ?>" title="edit this program"></a>
<?php
		}
?>
						</h2>
						<p class=meta>
							<time class=posted title="released <?=$prog->released->title; ?>" datetime="<?=$prog->released->datetime; ?>"><?=$prog->released->display; ?></time>
							<span class=schoolsubject><?=$prog->subject; ?></span>
							<span class=calculator><?=$prog->model; ?></span>
						</p>
					</header>
					<?=$prog->deschtml; ?>
					<p class=downloads>
						<a class="zip action" href="files/<?=$prog->url; ?>.zip"><?=$prog->url; ?>.zip</a>
<?php
		if($prog->ticalc) {
?>
						<a class="ticalc action" href="http://www.ticalc.org/archives/files/fileinfo/<?=floor($prog->ticalc / 100); ?>/<?=$prog->ticalc; ?>.html"><?=htmlspecialchars($prog->name); ?> on ticalc.org</a>
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
