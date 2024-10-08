<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'application.php';

class ApplicationPage extends Page {
	private static ?Application $application = null;

	public function __construct() {
		self::$application = Application::FromQueryString(self::RequireDatabase(), 'url');
		if (!self::$application)
			self::NotFound('404 application not found', 'sorry, we don’t seem to have an application by that name.  try the list of <a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/">all applications</a>.');
		parent::__construct(self::$application->Title);
	}

	protected static function MainContent(): void {
?>
		<h1>
			<img class=icon src="files/<?= self::$application->ID; ?>.png" alt="">
			<?= htmlspecialchars(self::$application->Title); ?>
		</h1>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		echo self::$application->Description;
		if (self::$application->GitHub) {
		?>
			<p><a class="action github" href="https://github.com/misterhaan/<?= self::$application->GitHub; ?>"><?= htmlspecialchars(self::$application->Title); ?> on github</a></p>
		<?php
		}
		if (self::$application->Wiki) {
		?>
			<p><a class="action documentation" href="https://wiki.track7.org/<?= self::$application->Wiki; ?>"><?= htmlspecialchars(self::$application->Title); ?> documentation</a></p>
		<?php
		}
		?>
		<section id=releases></section>
	<?php
		self::ShowComments(self::$application->Post);
	}

	private static function ShowAdminActions(): void {
	?>
		<nav class=actions>
			<a class=edit href="editapp?id=<?= self::$application->ID; ?>">edit</a>
			<a class=new href="addrel?app=<?= self::$application->ID; ?>">add release</a>
		</nav>
<?php
	}
}
new ApplicationPage();
