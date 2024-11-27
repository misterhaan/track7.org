<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'guide.php';

class FullGuide extends Page {
	private static ?Guide $guide = null;

	public function __construct() {
		self::$guide = Guide::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$guide)
			self::NotFound('404 guide not found', '<p>sorry, we don’t seem to have a guide by that name. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all guides</a>.</p>');
		parent::__construct(self::$guide->Title . ' - guides');
		self::$guide->LogView(self::$db);
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$guide->Title); ?></h1>
		<p class=guidemeta>
			<span class=guidelevel title="<?= self::$guide->Level; ?> level"><?= self::$guide->Level; ?></span>
			<span class=tags><?php self::ShowTagLinks(); ?></span>
			<span class=views title="viewed <?= self::$guide->Views; ?> times"><?= self::$guide->Views; ?></span>
			<?php
			self::ShowRating(self::$guide->Rating, self::$guide->VoteCount);
			?>
			<time class=posted datetime="<?= self::$guide->Instant->DateTime; ?>" title="posted <?= !self::$guide->Posted ? self::$guide->Instant->Tooltip : self::$guide->Instant->Tooltip . ' (originally ' . self::$guide->Posted . ')'; ?>"><?= self::$guide->Instant->Display; ?></time>
			<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
		</p>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminMenu();
		?>
		<div id=summary>
			<?php
			echo self::$guide->Summary;
			?>
		</div>
		<div id=guidechapters></div>
		<?php
		if (self::$guide->Published) {
			self::ShowVoteWidget(self::$guide->Post, self::$guide->Vote, 'how was it?');
			self::ShowComments(self::$guide->Post);
		}
	}

	private static function ShowTagLinks() {
		$first = true;
		foreach (self::$guide->Tags as $tag) {
			if ($first)
				$first = false;
			else
				echo ', ';
			echo '<a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/' . $tag . '/" title="guides tagged ' . $tag . '">' . $tag . '</a>';
		}
	}

	private static function ShowAdminMenu() {
		?>
		<nav class=actions>
			<a class=edit href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php?id=<?= self::$guide->ID; ?>">edit this guide</a>
			<?php
			if (!self::$guide->Published) {
			?>
				<a class=publish href="/api/guide.php/publish/<?= self::$guide->ID; ?>" @click.prevent=Publish>publish this guide</a>
				<a class=del href="/api/guide.php/id/<?= self::$guide->ID; ?>" @click.prevent=Delete>delete this guide</a>
			<?php
			}
			?>
		</nav>
<?php
	}
}
new FullGuide();
