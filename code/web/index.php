<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class ScriptIndex extends Page {
	public function __construct() {
		parent::__construct('web scripts');
	}

	protected static function MainContent(): void {
?>
		<h1>web scripts</h1>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		?>
		<p>
			these web scripts include snippets you can work into your own website,
			userscripts that can customize other websites, and web applications.
			they’re based on web technologies such as html, css, php, mysql, and
			javascript.
		</p>

		<nav id=webscripts></nav>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<nav class=actions><a class=new href="editscr.php">add a web script</a></nav>
<?php
	}
}
new ScriptIndex();
