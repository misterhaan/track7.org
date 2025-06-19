<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class Track7Index extends Page {
	public function __construct() {
		parent::__construct('track7');
	}

	protected static function MainContent(): void {
?>
		<h1><img alt=track7 src="/images/track7.png"></h1>
		<?php
		self::ShowFeatures();
		self::ShowAdminActions();
		?>
		<div id=latestactivity></div>
	<?php
	}

	private static function ShowFeatures(): void {
	?>
		<section id=features>
			<nav>
				<?php
				require_once 'subsite.php';
				$subsites = Subsite::List(self::RequireDatabase());
				foreach ($subsites as $subsite) {
				?>
					<a href="/<?= $subsite->ID; ?>/" title="<?= $subsite->CallToAction; ?>">
						<img src="/<?= $subsite->ID; ?>/favicon.png" alt="">
						<?= $subsite->Name; ?>
					</a>
				<?php
				}
				if (self::HasAdminSecurity()) {
				?>
					<a href="/tools/" title="administer track7">
						<img src="/favicon.png" alt="">
						tools
					</a>
				<?php
				}
				?>
			</nav>
		</section>
		<?php
	}

	private static function ShowAdminActions(): void {
		if (self::HasAdminSecurity()) {
		?>
			<nav class=actions><a class=new href="/updates/new.php">add update message</a></nav>
<?php
		}
	}
}
new Track7Index();
