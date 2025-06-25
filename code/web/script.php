<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'script.php';

class ScriptPage extends Page {
	private static ?Script $script = null;

	public function __construct() {
		self::$script = Script::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$script)
			self::NotFound('404 script not found', 'sorry, we don’t seem to have a script by that name. try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all scripts</a>.');
		parent::__construct(self::$script->Title . ' - web scripts - software');
	}

	protected static function MainContent(): void {
?>
		<h1><?= htmlspecialchars(self::$script->Title); ?></h1>
		<p class=meta>
			<span class=scripttype><?= self::$script->Type; ?></span>
			<time class=posted title="released <?= self::$script->Instant->Tooltip; ?>" datetime="<?= self::$script->Instant->DateTime; ?>"><?= self::$script->Instant->Display; ?></time>
		</p>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		echo self::$script->Description;
		?>
		<h2>files</h2>
		<p class="downloads">
			<a class="action zip" href="<?= self::$script->Download; ?>">download</a>
			<?php
			if (self::$script->GitHub) {
			?>
				<a class="action github" href="https://github.com/misterhaan/<?= self::$script->GitHub; ?>">github</a>
			<?php
			}
			?>
		</p>
		<?php
		if (self::$script->Instructions || self::$script->Wiki) {
		?>
			<h2>instructions</h2>
			<?php
			echo self::$script->Instructions;
			if (self::$script->Wiki) {
			?>
				<p class=calltoaction><a class="action wiki" href="https://wiki.track7.org/<?= self::$script->Wiki; ?>">read more on the track7 wiki</a></p>
		<?php
			}
		}
		self::ShowComments(self::$script->Post);
	}

	private static function ShowAdminActions(): void {
		?>
		<nav class=actions>
			<a class=edit href="editscr?id=<?= self::$script->ID; ?>">edit this script</a>
		</nav>
<?php
	}
}
new ScriptPage();
