<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class TransitionIndex extends Page {
	public function __construct() {
		if (!self::HasAdminSecurity())
			self::NotFound();
		parent::__construct('database transitions');
	}

	protected static function MainContent(): void {
?>
		<h1>database transitions</h1>

		<ul>
			<li><a href=users.php>users</a></li>
			<li><a href=photos.php>photo album</a></li>
			<li><a href=art.php>art</a></li>
			<li><a href=blog.php>blog</a></li>
			<li><a href=code.php>code</a></li>
			<li><a href=forum.php>forum</a></li>
			<li><a href=guides.php>guides</a></li>
			<li><a href=legos.php>lego models</a></li>
			<li><a href=stories.php>stories</a></li>
			<li><a href=updates.php>updates</a></li>
			<li><a href=activity.php>activity</a></li>
			<li><a href=logins.php>logins</a></li>
			<li><a href=settings.php>settings</a></li>
			<li><a href=messages.php>messages</a></li>
		</ul>

<?php
	}
}
new TransitionIndex();
