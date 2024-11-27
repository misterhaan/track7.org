<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';
require_once 'photo.php';

class AlbumPhoto extends Page {
	private const Subsite = 'album';
	private static ?string $tag = null;
	private static ?Photo $photo = null;
	private static ?PrevNext $prevNext = null;

	public function __construct() {
		try {
			$tag = Tag::FromQueryString(self::RequireDatabase(), self::Subsite);
		} catch (Exception) {
			self::Redirect(isset($_GET['photo']) && $_GET['photo'] ? $_GET['photo'] : '');
		}
		self::$tag = $tag ? $tag->Name : null;

		self::$photo = Photo::FromQueryString(self::RequireDatabase());
		if (!self::$photo)
			self::NotFound('404 photo not found', '<p>sorry, we don’t have a photo by that name. try picking one from <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>the gallery</a>.</p>');

		$title = self::$photo->Title;
		if (self::$tag)
			$title .= ' - ' . self::$tag;
		parent::__construct("$title - photos");
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$photo->Title); ?></h1>
	<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		self::ShowPrevNext();
		self::ShowPhoto();
		self::ShowMetadata();
		echo self::$photo->Story;
		self::ShowPrevNext();
		self::ShowComments(self::$photo->Post);
	}

	private static function ShowAdminActions(): void {
	?>
		<nav class=actions><a class=edit href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . self::$photo->ID; ?>">edit this photo</a></nav>
	<?php
	}

	private static function ShowPrevNext(): void {
		if (!self::$prevNext)
			self::FindPrevNext();
	?>
		<nav class=tagprevnext>
			<?php
			if (self::$prevNext->Next) {
				$tooltip = 'see the photo posted after this';
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
				<a class=tag title="see all photos posted in <?= self::$tag; ?>" href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/' . self::$tag . '/'; ?>"><?= self::$tag; ?></a>
			<?php
			} else {
			?>
				<a class=gallery title="see all photos" href="<?= dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
			<?php
			}
			if (self::$prevNext->Prev) {
				$tooltip = 'see the photo posted before this';
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

		$select = self::$db->prepare('select ps.title, ps.url from post_tag as pt left join post as ps on ps.id=pt.post where pt.tag=? and ps.instant<from_unixtime(?) and ps.subsite=\'album\' order by ps.instant desc limit 1');
		$select->bind_param('si', self::$tag, self::$photo->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Prev = new TitledLink($title, $url);

		$select = self::$db->prepare('select ps.title, ps.url from post_tag as pt left join post as ps on ps.id=pt.post where pt.tag=? and ps.instant>from_unixtime(?) and ps.subsite=\'album\' order by ps.instant limit 1');
		$select->bind_param('si', self::$tag, self::$photo->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Next = new TitledLink($title, $url);
	}

	private static function FindPrevNextAll(): void {
		self::$prevNext = new PrevNext();

		$select = self::$db->prepare('select title, url from post where instant<from_unixtime(?) and subsite=\'album\' order by instant desc limit 1');
		$select->bind_param('i', self::$photo->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Prev = new TitledLink($title, $url);

		$select = self::$db->prepare('select title, url from post where instant>from_unixtime(?) and subsite=\'album\' order by instant limit 1');
		$select->bind_param('i', self::$photo->Instant);
		$select->execute();
		$select->bind_result($title, $url);
		while ($select->fetch())
			self::$prevNext->Next = new TitledLink($title, $url);
	}

	private static function ShowPhoto(): void {
		if (self::$photo->Youtube) {
		?>
			<p><iframe class=photo width="640" height="385" src="https://www.youtube.com/embed/<?= self::$photo->Youtube; ?>" allowfullscreen></iframe></p>
		<?php
		} else {
		?>
			<p><img class=photo src="<?= dirname($_SERVER['SCRIPT_NAME']) . '/photos/' . self::$photo->ID; ?>.jpeg"></p>
		<?php
		}
	}

	private static function ShowMetadata(): void {
		require_once 'formatDate.php';
		$posted = new TimeTagData(self::RequireUser(), 'smart', self::$photo->Instant, FormatDate::Long);
		?>
		<p class=photometa>
			<?php
			if (self::$photo->Taken) {
				$taken = new TimeTagData(self::$user, 'smart', self::$photo->Taken, FormatDate::Long);
			?>
				<time class=taken datetime="<?= $taken->DateTime; ?>" title="taken <?= $taken->Tooltip; ?>"><?= $taken->Display; ?></time>
			<?php
			} elseif (self::$photo->Year) {
			?>
				<time class=taken datetime=<?= self::$photo->Year; ?> title="taken in <?= self::$photo->Year; ?>"><?= self::$photo->Year; ?></time>
			<?php
			}
			?>
			<time class=posted datetime="<?= $posted->DateTime; ?>" title="posted <?= $posted->Tooltip; ?>"><?= $posted->Display; ?></time>
			<?php
			self::ShowTags(self::$photo->Post);
			?>
		</p>
<?php
	}
}
new AlbumPhoto();
