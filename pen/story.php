<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'story.php';

class StoryPage extends Page {
	private static ?Story $story;

	public function __construct() {
		self::$story = Story::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$story)
			self::NotFound('404 story not found', '<p>sorry, we don’t seem to have a story by that name. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all stories</a>.</p>');
		if (self::$story->SeriesID && (!isset($_GET['series']) || $_GET['series'] != self::$story->SeriesID))
			self::Redirect(self::$story->SeriesID . '/' . self::$story->ID);
		if (!self::$story->SeriesID && isset($_GET['series']))
			self::Redirect(self::$story->ID);

		if (self::$story->SeriesID)
			parent::__construct(self::$story->Title . ' - ' . self::$story->SeriesTitle . ' - stories');
		else
			parent::__construct(self::$story->Title . ' - stories');
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$story->Title); ?></h1>
		<?php
		if (self::$story->SeriesID || self::$story->Instant) {
		?>
			<p class=postmeta>
				<?php
				if (self::$story->SeriesID) {
				?>
					story <?= +self::$story->Number; ?> of <?= +self::$story->NumStories; ?> in
					<a href="."><?= htmlspecialchars(self::$story->SeriesTitle); ?></a>
				<?php
				}
				if (self::$story->Instant) {
				?>
					published <time datetime="<?= self::$story->Instant->DateTime; ?>" title="<?= self::$story->Instant->Tooltip; ?>"><?= htmlspecialchars(self::$story->Instant->Display); ?></time>
				<?php
				}
				?>
			</p>
<?php
		}
		echo self::$story->HTML;
		// TODO:  links to prev / next story and prev / next in series
		self::ShowComments(self::$story->Post);
	}
}
new StoryPage();
