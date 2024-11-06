<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class ForumIndex extends Page {
	private const Subsite = 'forum';
	private static ?ActiveTag $tag = null;

	public function __construct() {
		try {
			self::$tag = ActiveTag::FromQueryString(self::RequireDatabase(), self::Subsite);
			parent::__construct(self::$tag ? self::$tag->Name . ' forum' : 'forum');
		} catch (DetailedException $de) {
			self::Error($de->getMessage());
		}
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$tag ? self::$tag->Name . ' forum' : 'forum'; ?></h1>
		<?php
		if (!self::$tag)
			self::ShowTagCloud('discussions');
		?>
		<div class=floatbgstop>
			<nav class=actions>
				<a class=new href="<?= dirname($_SERVER['PHP_SELF']); ?>/start.php">start a new discussion</a>
				<?php
				if (self::$tag && self::HasAdminSecurity()) {
				?>
					<a href="#tagedit" class=edit>edit tag description</a>
				<?php
				}
				?>
			</nav>
		</div>
		<?php
		if (self::$tag)
			self::$tag->ShowInfo(self::HasAdminSecurity());
		?>
		<div id=discussionlist></div>
<?php
	}
}
new ForumIndex();
