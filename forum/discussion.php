<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'forum.php';

class ForumDiscussion extends Page {
	private static ?Discussion $discussion = null;

	public function __construct() {
		self::$discussion = Discussion::FromQueryString(self::RequireDatabase());
		if (!self::$discussion)
			self::NotFound('404 discussion not found', '<p>sorry, we don’t seem to have a discussion with that id. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all discussions</a>.</p>');
		parent::__construct(self::$discussion->Title . ' - forum');
	}

	protected static function MainContent(): void {
?>
		<h1 data-discussion=<?= +self::$discussion->ID; ?>><?= htmlspecialchars(self::$discussion->Title); ?></h1>
		<p class=meta><span class=tags>
				<?php
				$tagCount = 0;
				foreach (self::$discussion->Tags as $tag) {
					$tagCount++;
				?>
					<a href="<?= dirname($_SERVER['SCRIPT_NAME']); ?>/<?= urlencode($tag); ?>/"><?= htmlspecialchars($tag); ?></a><?= $tagCount < count(self::$discussion->Tags) ? ',' : '' ?>
				<?php
				}
				?>
			</span></p>
<?php
		self::ShowComments(self::$discussion->ID);
	}
}
new ForumDiscussion();
