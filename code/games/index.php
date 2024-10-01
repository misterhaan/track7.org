<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/gameworld.php';

class GamewowrldIndex extends Page {
	public function __construct() {
		parent::__construct('game worlds');
	}

	protected static function MainContent(): void {
?>
		<h1>game worlds</h1>
		<p>
			when i was in high school i discovered zzt (and later megazeux) and made
			some game worlds for them. mostly i named them weirdland with a
			subtitle specific to that game, even though none of them really have
			anything to do with each other. i finished three of them and then
			started two more. to play them you will need the game environment,
			which back when i had them were available free as shareware.
		</p>
		<nav id=gamewlds>
			<?php
			foreach (Gameworld::List(self::RequireDatabase(), self::RequireUser()) as $world) {
			?>
				<article id="<?= $world->ID; ?>">
					<header>
						<h2><?= htmlspecialchars($world->Title); ?></h2>
						<p class=meta>
							<time class=posted title="released <?= $world->Instant->Tooltip; ?>" datetime="<?= $world->Instant->DateTime; ?>"><?= $world->Instant->Display; ?></time>
							<span class=gameengine><?= $world->Engine; ?></span>
						</p>
					</header>
					<img class=screenshot alt="" src="files/<?= $world->ID; ?>.png">
					<?= $world->Description; ?>
					<p class=downloads>
						<a class="zip action" href="files/<?= $world->ID; ?>.zip"><?= $world->ID; ?>.zip</a>
						<?php
						if ($world->DMZX) {
						?>
							<a class="dmzx action" href="http://vault.digitalmzx.net/show.php?id=<?= $world->DMZX; ?>"><?= htmlspecialchars($world->Title); ?> in the dmzx vault</a>
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
}
new GamewowrldIndex();
