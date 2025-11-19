<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class BlogIndex extends Page {
	private const Subsite = 'bln';
	private static ?ActiveTag $tag = null;

	public function __construct() {
		try {
			self::$tag = ActiveTag::FromQueryString(self::RequireDatabase(), self::Subsite);
			parent::__construct(self::$tag ? self::$tag->Name . ' - blog' : 'blog');
		} catch (DetailedException $de) {
			self::Error($de->getMessage());
		}
	}

	protected static function MainContent(): void {
		$headingtext = 'latest' . (self::$tag ? ' ' . htmlspecialchars(self::$tag->Name) : '') . ' blog entries';
?>
		<h1><?= $headingtext; ?></h1>
		<?php
		if (!self::$tag)
			self::ShowTagCloud('entries');
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		if (self::$tag)
			self::$tag->ShowInfo(self::HasAdminSecurity());
		?>
		<section id=blogentries></section>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<nav class=actions>
			<a href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>start a new entry</a>
			<?php
			if (self::$tag) {
			?>
				<a href="#tagedit" class=edit>edit tag description</a>
			<?php
			}
			?>
		</nav>

<?php
	}
}
new BlogIndex();
