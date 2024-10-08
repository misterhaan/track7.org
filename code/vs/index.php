<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'application.php';

class ApplicationIndex extends Page {
	public function __construct() {
		parent::__construct('applications');
	}

	protected static function MainContent(): void {
?>
		<h1>applications</h1>
		<?php
		if (self::HasAdminSecurity())
			self::ShowAdminActions();
		?>
		<p>
			each application is available in a windows installer package, unless
			it’s older than windows installer in which case it’s a zip file with
			a setup.exe and a couple other files.
		</p>
		<p>
			source code for each release is also provided so you can customize and /
			or learn from my work. newer releases are on github, preceded by
			subversion, and zip files before that.
		</p>
		<nav id=vsapps></nav>
	<?php
	}

	private static function ShowAdminActions() {
	?>
		<nav class=actions><a class=new href=editapp>add an application</a></nav>
<?php
	}
}
new ApplicationIndex();
