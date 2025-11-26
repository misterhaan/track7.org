<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';
require_once 'blog.php';

class BlogEntry extends Page {
	private const Subsite = 'bln';
	private static ?string $tag = null;
	private static ?Blog $entry = null;

	public function __construct() {
		// handle old tag= format for tags
		if (isset($_GET['name']) && substr($_GET['name'], 0, 4) == 'tag=')
			self::Redirect(substr($_GET['name'], 4) . '/');

		try {
			$tag = Tag::FromQueryString(self::RequireDatabase(), self::Subsite);
		} catch (Exception) {
			self::Redirect(isset($_GET['name']) && $_GET['name'] ? $_GET['name'] : '');
		}
		self::$tag = $tag ? $tag->Name : null;

		self::$entry = Blog::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$entry)
			self::NotFound('404 blog entry not found', '<p>sorry, we don’t seem to have a blog entry by that name. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all blog entries</a>.</p>');

		$title = self::$entry->Title;
		if (self::$tag)
			$title .= ' - ' . self::$tag;
		parent::__construct("$title - blog");
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$entry->Title); ?></h1>
	<?php
		self::ShowMetadata();
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		echo self::$entry->HTML;
		if (self::$entry->Published)
			self::ShowComments(self::$entry->Post);
	}

	private static function ShowMetadata(): void {
	?>
		<p class=meta>
			<span class=tags title="<?= count(self::$entry->Tags) == 1 ? '1 tag' : count(self::$entry->Tags) . ' tags'; ?>">
				<?php
				$notFirst = false;
				foreach (self::$entry->Tags as $tagName) {
					if ($notFirst)
						echo ', ';
					echo '<a class=tag href="' . (self::$tag ? '../' : '') . $tagName . '/" title="entries tagged ' . $tagName . '">' . $tagName . '</a>';
					$notFirst = true;
				}
				?>
			</span>
			<?php
			if (self::$entry->Instant) {
			?>
				<time class=posted datetime="<?= self::$entry->Instant->DateTime; ?>" title="posted <?= self::$entry->Instant->Tooltip; ?>"><?= self::$entry->Instant->Display; ?></time>
			<?php
			}
			?>
			<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
		</p>
	<?php
	}

	private static function ShowAdminActions(): void {
	?>
		<nav class=actions>
			<a class=edit href="<?= dirname($_SERVER['PHP_SELF']); ?>/edit.php?id=<?= self::$entry->ID; ?>">edit</a>
			<?php
			if (!self::$entry->Published) {
			?>
				<a class=publish href="/api/blog.php/publish/<?= self::$entry->Post; ?>" @click.prevent=Publish>publish</a>
				<a class=del href="/api/blog.php/entry/<?= self::$entry->ID; ?>" @click.prevent=Delete>delete</a>
				<Transition name=fadeout><span class=success v-if=showPublishSuccess>successfully published!</span></Transition>
			<?php
			}
			?>
		</nav>
<?php
	}
}
new BlogEntry();
