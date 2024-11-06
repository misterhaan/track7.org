<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'application.php';

class ReleaseEdit extends Page {
	private static ?Application $app;

	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound('404 application not found', '<p>sorry, we don’t have an application by that name.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all applications</a>.</p>');

		self::$app = Application::FromQueryString(self::RequireDatabase(), 'app');
		if (!self::$app)
			self::NotFound('404 application not found', '<p>can’t find application you’re trying to release.  try the list of <a href=' . dirname($_SERVER['SCRIPT_NAME']) . '>all applications</a>.</p>');

		parent::__construct('add release - ' . htmlspecialchars(self::$app->Title));
	}

	protected static function MainContent(): void {
?>
		<h1>add release: <?= htmlspecialchars(self::$app->Title); ?></h1>
		<div id=addrel></div>
<?php
	}
}
new ReleaseEdit();
