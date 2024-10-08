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

die;
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
if (isset($_GET['app']))
	if ($app = $db->query('select id, name, url from code_vs_applications where id=\'' . +$_GET['app'] . '\' limit 1'))
		if ($app = $app->fetch_object()) {
			$html = new t7html(['vue' => true]);
			$html->Open('add release - ' . htmlspecialchars($app->name));
	?>
	<form id=addrel method=post enctype="multipart/form-data" v-on:submit.prevent=Save>
		<input type=hidden name=app value=<?= $app->id; ?>>
		<label class=multiline>
			<span class=label>changes:</span>
			<span class=field><textarea name=changelog></textarea></span>
		</label>
		<button :disabled=saving :class="{working: saving}">save</button>
	</form>
<?php
			$html->Close();
		} else;  // app not found
	else;  // db error
else;  // app not provided
