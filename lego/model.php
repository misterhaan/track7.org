<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'lego.php';

class LegoModel extends Page {
	private const Subsite = 'lego';
	private static ?Lego $lego = null;
	private static ?PrevNext $prevNext = null;

	public function __construct() {
		self::$lego = Lego::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$lego)
			self::NotFound('404 lego model not found', '<p>sorry, we don’t have a lego model by that name. try picking one from <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>the gallery</a>.</p>');
		parent::__construct(self::$lego->Title . ' - lego models');
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$lego->Title); ?></h1>
	<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		self::ShowPrevNext();
		self::ShowModel();
		self::ShowMetadata();
		self::ShowDownloads();
		self::ShowDescription();
		self::ShowVoteWidget(self::$lego->Post, self::$lego->Vote, 'how do you like it?');
		self::ShowPrevNext();
		self::ShowComments(self::$lego->Post);
	}

	private static function ShowAdminActions(): void {
	?>
		<nav class=actions><a class=edit href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . self::$lego->ID; ?>">edit this lego model</a></nav>
	<?php
	}

	private static function ShowPrevNext(): void {
		if (!self::$prevNext)
			self::FindPrevNext();
	?>
		<nav class="tagprevnext actions">
			<?php
			if (self::$prevNext->Next) {
			?>
				<a class=prev title="see the lego model posted after this" href="<?= self::$prevNext->Next->URL; ?>"><?= htmlspecialchars(self::$prevNext->Next->Title); ?></a>
			<?php
			}
			?>
			<a class=gallery title="see all lego models" href="<?= dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
			<?php
			if (self::$prevNext->Prev) {
			?>
				<a class=next title="see the lego model posted before this" href="<?= self::$prevNext->Prev->URL; ?>"><?= htmlspecialchars(self::$prevNext->Prev->Title); ?></a>
			<?php
			}
			?>
		</nav>
	<?php
	}

	private static function FindPrevNext(): void {
		self::RequireDatabase();
		self::$prevNext = new PrevNext();

		$select = self::$db->prepare('select title, url from post where subsite=\'lego\' and instant<from_unixtime(?) order by instant desc limit 1');
		$select->bind_param('i', self::$lego->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Prev = new TitledLink($title, $url);

		$select = self::$db->prepare('select title, url from post where subsite=\'lego\' and instant>from_unixtime(?) order by instant limit 1');
		$select->bind_param('i', self::$lego->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Next = new TitledLink($title, $url);
	}

	private static function ShowModel(): void {
	?>
		<p><img class=lego src="<?= dirname($_SERVER['SCRIPT_NAME']) . '/data/' . self::$lego->ID . '.png'; ?>"></p>
	<?php
	}

	private static function ShowMetadata(): void {
		require_once 'formatDate.php';
		$posted = new TimeTagData(self::RequireUser(), 'smart', self::$lego->Instant, FormatDate::Long);
	?>
		<p class="image meta">
			<time class=posted datetime="<?= $posted->DateTime; ?>" title="posted <?= $posted->Tooltip; ?>"><?= $posted->Display; ?></time>
			<span class=pieces><?= self::$lego->Pieces; ?> pieces</span>
			<?php
			self::ShowRating(self::$lego->Rating, self::$lego->VoteCount);
			?>
		</p>
	<?php
	}

	private static function ShowDownloads(): void {
	?>
		<p class="actions image">
			<a class=pdf href="data/<?= self::$lego->ID; ?>.pdf">step-by-step instructions</a>
			<a class=download download href="data/<?= self::$lego->ID; ?>.ldr">ldraw data</a>
		</p>
<?php
	}

	private static function ShowDescription(): void {
		echo self::$lego->Description;
	}
}
new LegoModel();
