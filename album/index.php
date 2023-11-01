<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class AlbumIndex extends Page {
	private const Subsite = 'album';
	private static ?Tag $tag = null;

	public function __construct() {
		self::$tag = Tag::FromQueryString(self::RequireDatabase(), self::Subsite);
		self::$bodytype = 'gallery';
		self::$rss = new TitledLink(
			self::$tag ? self::$tag->Name . ' photos' : 'photos',
			dirname($_SERVER['PHP_SELF']) . '/feed.rss' . (self::$tag ? '?tags=' . self::$tag->Name : '')
		);
		parent::__construct(self::$tag ? self::$tag->Name . ' - photo album' : 'photo album');
	}

	protected static function MainContent(): void {
		$headingtext = 'photo album' . (self::$tag ? ' — ' . self::$tag->Name : '');
?>
		<h1>
			<?= $headingtext; ?>

			<a class=feed href="<?= self::$rss->URL ?>" title="rss feed of <?= self::$tag ? self::$tag->Name : 'all'; ?> photos"></a>
		</h1>
		<?php
		if (!self::$tag)
			self::ShowTags('photos');
		if (self::$user->IsAdmin())
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
