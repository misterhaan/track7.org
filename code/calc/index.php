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
if($progs = $db->query('select p.id, p.url, p.name, p.released, s.name as subject, m.name as model, p.deschtml from code_calc_progs as p left join code_calc_subject as s on s.id=p.subject left join code_calc_model as m on m.id=p.model order by p.released desc')) {
?>
			<nav id=calcprogs>
<?php
	while($prog = $progs->fetch_object()) {
		$prog->released = t7format::TimeTag('smart', $prog->released, 'M j, Y \a\t g:i a');
?>
				<article id="<?php echo $prog->url; ?>">
					<header>
						<h2>
							<?php echo htmlspecialchars($prog->name); ?>
<?php
		if($user->IsAdmin()) {
?>
							<a class="edit action" href="editprog.php?id=<?php echo $prog->id; ?>" title="edit this program"></a>
<?php
		}
?>
						</h2>
						<p class=guidemeta>
							<time class=posted title="released <?php echo $prog->released->title; ?>" datetime="<?php echo $prog->released->datetime; ?>"><?php echo $prog->released->display; ?></time>
							<span class=schoolsubject><?php echo $prog->subject; ?></span>
							<span class=calculator><?php echo $prog->model; ?></span>
						</p>
					</header>
					<?php echo $prog->deschtml; ?>
					<p class=calltoaction><a class="zip action" href="files/<?php echo $prog->url; ?>.zip"><?php echo $prog->url; ?>.zip</a></p>
				</article>
<?php
	}
?>
			</nav>
<?php
}
$html->Close();
