<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/calcProg.php';

class CalcProgIndex extends Page {
	public function __construct() {
		parent::__construct('calculator programs');
	}

	protected static function MainContent(): void {
?>
		<h1>calculator programs</h1>
		<p>
			i’ve used all of these programs on a ti-86 calculator, and most of them
			on a ti-85. i don’t think ti makes those anymore and i’m not sure if
			any of their current models can run these programs. all programs are
			available for download as as zip file that contains an 85_ or 86_ file
			that can be transfered to a calculator with a utility such as connect-85
			or the official texas instruments software, or at least they could back
			in 1998.
		</p>
		<nav id=calcprogs>
			<?php
			foreach (CalcProg::List(self::RequireDatabase(), self::RequireUser()) as $prog) {
			?>
				<article id="<?= $prog->ID; ?>">
					<header>
						<h2><?= htmlspecialchars($prog->Title); ?></h2>
						<p class=meta>
							<time class=posted title="released <?= $prog->Instant->Tooltip; ?>" datetime="<?= $prog->Instant->DateTime; ?>"><?= $prog->Instant->Display; ?></time>
							<span class=schoolsubject><?= $prog->Subject; ?></span>
							<span class=calculator><?= $prog->Model; ?></span>
						</p>
					</header>
					<?= $prog->Description; ?>
					<p class=downloads>
						<a class="zip action" href="files/<?= $prog->ID; ?>.zip"><?= $prog->ID; ?>.zip</a>
						<a class="ticalc action" href="http://www.ticalc.org/archives/files/fileinfo/<?= floor($prog->TiCalc / 100); ?>/<?= $prog->TiCalc; ?>.html"><?= htmlspecialchars($prog->Title); ?> on ticalc.org</a>
					</p>
				</article>
			<?php
			}
			?>
		</nav>
<?php
	}
}
new CalcProgIndex();
