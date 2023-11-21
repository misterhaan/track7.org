<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class ArtIndex extends Page {
	private const Subsite = 'art';
	private static ?ActiveTag $tag = null;

	public function __construct() {
		self::$tag = ActiveTag::FromQueryString(self::RequireDatabase(), self::Subsite);
		self::$bodytype = 'gallery';
		parent::__construct(self::$tag ? self::$tag->Name . ' - art' : 'art');
	}

	protected static function MainContent(): void {
		$headingtext = 'visual art' . (self::$tag ? ' — ' . self::$tag->Name : '');
?>
		<h1><?= $headingtext; ?></h1>
		<?php
		if (!self::$tag)
			self::ShowTagCloud('art');
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		if (self::$tag)
			self::$tag->ShowInfo(self::HasAdminSecurity());
		?>
		<section id=visualart></section>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<div class=floatbgstop>
			<nav class=actions>
				<a href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add art</a>
				<?php
				if (self::$tag) {
				?>
					<a href="#tagedit" class=edit>edit tag description</a>
				<?php
				}
				?>
			</nav>
		</div>

<?php
	}
}
new ArtIndex();
