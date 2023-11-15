<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class AlbumIndex extends Page {
	private const Subsite = 'album';
	private static ?ActiveTag $tag = null;

	public function __construct() {
		try {
			self::$tag = ActiveTag::FromQueryString(self::RequireDatabase(), self::Subsite);
		} catch (Exception) {
			self::Redirect();
		}

		self::$bodytype = 'gallery';
		parent::__construct(self::$tag ? self::$tag->Name . ' - photo album' : 'photo album');
	}

	protected static function MainContent(): void {
		$headingtext = 'photo album' . (self::$tag ? ' — ' . self::$tag->Name : '');
?>
		<h1><?= $headingtext; ?></h1>
		<?php
		if (!self::$tag)
			self::ShowTagCloud('photos');
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		if (self::$tag)
			self::$tag->ShowInfo(self::HasAdminSecurity());
		?>
		<section id=albumphotos></section>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<div class=floatbgstop>
			<nav class=actions>
				<a href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add a photo or video</a>
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
new AlbumIndex();
