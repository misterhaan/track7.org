<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class Forbidden extends Page {
	public function __construct() {
		parent::__construct('401 forbidden');
	}

	protected static function MainContent(): void {
?>
		<h1>403 no coming in, please</h1>

		<p>
			the server thinks you’re a bot or a spammer. or sometimes it means you
			tried to go to a directory with no index. if you are actually some sort
			of lifeform (a.i. does not count) and you came here from a site that has
			been added to the spammers list, you can probably get where you wanted
			to go by following this link:
		</p>
		<p class=calltoaction><a class=action href="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">try again</a></p>
		<p>
			if you’re not a spammer or a bot and are wondering why you’re seeing
			this page, either your browser is lying to the server, or it identifies
			itself in a way that bots usually do. user-agent strings that match
			“one or two words, possibly followed by a 1-, 2-, or 3-segment version
			number” are considered useless bots and are not allowed. also certain
			urls passed through the HTTP Referer header which have suspiciously
			showed up in my statistics data are blocked. if you have set your
			browser to send a user-agent that matches the above description, you
			will need to change it to be able to view this site. i don't know of
			any browsers who send such a useragent by default. the blocked referers
			are either porn sites or spam sites, but if you by chance were visiting
			one before coming here, your browser may have told the server where you
			came from and caused the server to assume you’re a spammer. clicking
			the link above should get you where you tried to go without logging an
			entry for the porn/spam site.
		</p>
		<p>
			sorry for any inconvenience this may have caused for actual people
			trying to view my site!
		</p>

		<dl>
			<?php
			if (isset($_SERVER['HTTP_REFERER'])) {
			?>
				<dt>HTTP_REFERER</dt>
				<dd><?= htmlspecialchars($_SERVER['HTTP_REFERER']); ?></dd>
			<?php
			}
			?>
			<dt>REMOTE_ADDR</dt>
			<dd><?= htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></dd>
			<dt>HTTP_USER_AGENT</dt>
			<dd><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></dd>
		</dl>

<?php
	}
}
new Forbidden();
