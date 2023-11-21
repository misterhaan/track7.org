<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';
require_once 'art.php';

class Artwork extends Page {
	private const Subsite = 'art';
	private static ?string $tag = null;
	private static ?Art $art = null;
	private static ?PrevNext $prevNext = null;

	public function __construct() {
		try {
			$tag = Tag::FromQueryString(self::RequireDatabase(), self::Subsite);
		} catch (Exception) {
			self::Redirect(isset($_GET['art']) && $_GET['art'] ? $_GET['art'] : '');
		}
		self::$tag = $tag ? $tag->Name : null;

		self::$art = Art::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$art)
			self::NotFound('404 art not found', '<p>sorry, we don’t have art by that name. try picking one from <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>the gallery</a>.</p>');

		$title = self::$art->Title;
		if (self::$tag)
			$title .= ' - ' . self::$tag;
		parent::__construct("$title - art");
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$art->Title); ?></h1>
	<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		self::ShowPrevNext();
		self::ShowArt();
		self::ShowMetadata();
		echo self::$art->Description;
		self::ShowVoteWidget(self::$art->Post, self::$art->Vote, 'how do you like it?');
		self::ShowPrevNext();
		self::ShowComments(self::$art->Post);
	}

	private static function ShowAdminActions(): void {
	?>
		<nav class=actions><a class=edit href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . self::$art->ID; ?>">edit this art</a></nav>
	<?php
	}

	private static function ShowPrevNext(): void {
		if (!self::$prevNext)
			self::FindPrevNext();
	?>
		<nav class=tagprevnext>
			<?php
			if (self::$prevNext->Next) {
				$tooltip = 'see the art posted after this';
				$url = explode('/', self::$prevNext->Next->URL);
				$url = '/' . $url[count($url) - 1];
				if (self::$tag) {
					$tooltip .= ' in ' . self::$tag;
					$url = '/' . self::$tag . $url;
				}
				$url = dirname($_SERVER['SCRIPT_NAME']) . $url;
			?>
				<a class=prev title="<?= $tooltip; ?>" href="<?= $url; ?>"><?= htmlspecialchars(self::$prevNext->Next->Title); ?></a>
			<?php
			}
			if (self::$tag) {
			?>
				<a class=tag title="see all art posted in <?= self::$tag; ?>" href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/' . self::$tag . '/'; ?>"><?= self::$tag; ?></a>
			<?php
			} else {
			?>
				<a class=gallery title="see all art" href="<?= dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
			<?php
			}
			if (self::$prevNext->Prev) {
				$tooltip = 'see the art posted before this';
				$url = explode('/', self::$prevNext->Prev->URL);
				$url = '/' . $url[count($url) - 1];
				if (self::$tag) {
					$tooltip .= ' in ' . self::$tag;
					$url = '/' . self::$tag . $url;
				}
				$url = dirname($_SERVER['SCRIPT_NAME']) . $url;
			?>
				<a class=next title="<?= $tooltip; ?>" href="<?= $url; ?>"><?= htmlspecialchars(self::$prevNext->Prev->Title); ?></a>
			<?php
			}
			?>
		</nav>
	<?php
	}

	private static function FindPrevNext(): void {
		self::RequireDatabase();
		if (self::$tag)
			self::FindPrevNextTagged();
		else
			self::FindPrevNextAll();
	}

	private static function FindPrevNextTagged(): void {
		self::$prevNext = new PrevNext();

		$select = self::$db->prepare('select p.title, p.url from post_tag as pt left join post as p on p.id=pt.post where subsite=\'art\' and pt.tag=? and (p.instant<from_unixtime(?) or p.instant=from_unixtime(?) and p.id<?) order by p.instant desc, p.id desc limit 1');
		$select->bind_param('siii', self::$tag, self::$art->Instant, self::$art->Instant, self::$art->Post);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Prev = new TitledLink($title, $url);

		$select = self::$db->prepare('select p.title, p.url from post_tag as pt left join post as p on p.id=pt.post where subsite=\'art\' and pt.tag=? and (p.instant>from_unixtime(?) or p.instant=from_unixtime(?) and p.id<?) order by p.instant, p.id limit 1');
		$select->bind_param('siii', self::$tag, self::$art->Instant, self::$art->Instant, self::$art->Post);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Next = new TitledLink($title, $url);
	}

	private static function FindPrevNextAll(): void {
		self::$prevNext = new PrevNext();

		$select = self::$db->prepare('select title, url from post where subsite=\'art\' and (instant<from_unixtime(?) or instant=from_unixtime(?) and id<?) order by instant desc, id desc limit 1');
		$select->bind_param('iii', self::$art->Instant, self::$art->Instant, self::$art->Post);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Prev = new TitledLink($title, $url);

		$select = self::$db->prepare('select title, url from post where subsite=\'art\' and (instant>from_unixtime(?) or instant=from_unixtime(?) and id>?) order by instant, id limit 1');
		$select->bind_param('iii', self::$art->Instant, self::$art->Instant, self::$art->Post);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Next = new TitledLink($title, $url);
	}

	private static function ShowArt(): void {
	?>
		<p><img class=art src="<?= dirname($_SERVER['SCRIPT_NAME']) . '/img/' . self::$art->ID . '.' . self::$art->Ext; ?>"></p>
	<?php
	}

	private static function ShowMetadata(): void {
		require_once 'formatDate.php';
	?>
		<p class="art meta">
			<?php
			if (self::$art->Instant) {
				$posted = new TimeTagData(self::$user, 'smart', self::$art->Instant, FormatDate::Long);
			?>
				<time class=posted datetime="<?= $posted->DateTime; ?>" title="posted <?= $posted->Tooltip; ?>"><?= $posted->Display; ?></time>
			<?php
			}
			self::ShowTags(self::$art->Post);
			?>
			<span class=rating data-stars=<?= round(self::$art->Rating * 2) / 2; ?> title="rated <?= self::$art->Rating; ?> stars by <?= self::$art->VoteCount == 0 ? 'nobody' : (self::$art->VoteCount == 1 ? '1 person' : self::$art->VoteCount . ' people'); ?>"></span>
			<?php
			if (self::$art->Deviation) {
			?>
				<a class=deviantart href="https://deviantart.com/art/<?= self::$art->Deviation; ?>" title="see <?= htmlspecialchars(self::$art->Title); ?> on deviantart">deviantart</a>
			<?php
			}
			?>
		</p>
<?php
	}
}
new Artwork();

class PrevNext {
	public ?TitledLink $Prev = null;
	public ?TitledLink $Next = null;
}

/**
 * Pairing of a title and a URL
 */
class TitledLink {
	/**
	 * Link title
	 */
	public string $Title = '';
	/**
	 * Link URL
	 */
	public string $URL = '';

	/**
	 * Default constructor
	 * @param $title Link title
	 * @param $url Link URL
	 */
	public function __construct(string $title, string $url) {
		$this->Title = $title;
		$this->URL = $url;
	}
}
