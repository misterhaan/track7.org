<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class GuideIndex extends Page {
	private const Subsite = 'guides';
	private static ?ActiveTag $tag = null;

	public function __construct() {
		try {
			self::$tag = ActiveTag::FromQueryString(self::RequireDatabase(), self::Subsite);
			parent::__construct(self::$tag ? self::$tag->Name . ' - guides' : 'guides');
		} catch (DetailedException $de) {
			self::Error($de->getMessage());
		}
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$tag ? self::$tag->Name . ' - guides' : 'latest guides'); ?></h1>
		<?php
		if (!self::$tag)
			self::ShowTagCloud('guides');
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		if (self::$tag)
			self::$tag->ShowInfo(self::HasAdminSecurity());
		?>
		<section id=guides></section>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<nav class=actions>
			<a href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>start a new guide</a>
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
new GuideIndex();
