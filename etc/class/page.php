<?php
require_once 'environment.php';

/**
 * HTML Page for track7
 */
abstract class Page extends Responder {
	private const siteTitle = 'track7';
	private const copyrightEndYear = 2023;

	protected static string $title = self::siteTitle;
	protected static string $bodytype = 'text';
	protected static ?TitledLink $rss = null;
	protected static array $importmap = [];
	protected static array $params = [];

	/**
	 * Generic constructor sets a title and starts output.
	 * @param $title Title of the page to show on the browser tab.
	 */
	public function __construct(string $title) {
		self::SetTitle($title);
		self::$importmap['jquery'] = '/jquery-3.7.1.min.js';
		self::$importmap['vue'] = file_exists($_SERVER['DOCUMENT_ROOT'] . '/vue.esm-browser.js') ? '/vue.esm-browser.js' : '/vue.esm-browser.prod.js';
		self::$importmap['autosize'] = '/autosize.esm.js';
		self::$importmap['tag'] = '/tag.js';
		self::$importmap['comment'] = '/comment.js';
		self::Send();
	}

	private static function SetTitle(string $title): void {
		if (strpos($title, self::siteTitle) === false)
			self::$title = $title . ' - ' . self::siteTitle;
		else
			self::$title = $title;
	}

	/**
	 * Output the main content of the page, starting with an h1 tag to show the title.
	 */
	protected abstract static function MainContent(): void;

	/**
	 * Send the page as a response.
	 */
	private static function Send(string $overrideMain = ''): void {
		header('X-Sven: look out for the fruits of life');
		header('Content-Type: text/html; charset=utf-8');
?>
		<!DOCTYPE html>
		<html lang=en>
		<?php
		self::SendHead();
		?>

		<body class=<?= self::$bodytype; ?>>
			<?php
			self::SendHeader();
			?>
			<main>
				<?php
				if ($overrideMain)
					echo $overrideMain;
				else
					try {
						static::MainContent();
					} catch (DetailedException $de) {
						self::DetailedError($de);
					}
				?>
			</main>
			<?php
			self::SendFooter();
			?>
		</body>

		</html>
	<?php
	}

	/**
	 * Show a cloud of tags
	 * @param string $pluralName what to call multiple of the item that can be tagged
	 */
	protected static function ShowTagCloud($pluralName): void {
	?>
		<nav class="tagcloud" data-plural-name="<?= $pluralName; ?>"></nav>
		<?php
	}

	protected static function ShowTags(int $post): void {
		$tags = Tag::ForPost(self::RequireDatabase(), $post);
		if (count($tags)) {
		?>
			<span class=tags>
				<?php
				foreach ($tags as $tag) {
				?>
					<a href="<?= dirname($_SERVER['SCRIPT_NAME']) . '/' . $tag; ?>/"><?= $tag; ?></a><?php if ($tag != $tags[count($tags) - 1]) echo ','; ?>
				<?php
				}
				?>
			</span>
		<?php
		}
	}

	protected static function ShowComments(int $post): void {
		?>
		<section id=comments data-post=<?= $post; ?>></section>
	<?php
	}

	/**
	 * End the page with a not found error.
	 * @param $title Not found error title
	 * @param $body HTML body of the not found error message
	 */
	protected static function NotFound(string $title, string $body) {
		header('HTTP/1.0 404 Not Found');
		self::SetTitle($title);
		self::Send("<h1>$title</h1>$body");
		die;
	}

	/**
	 * Show a detailed error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected static function DetailedError(mixed $error, string $detail = null): void {
		if (self::HasAdminSecurity())
			if ($error instanceof DetailedException)
				self::Error($error->getDetailedMessage());
			elseif ($detail)
				self::Error("$error:  $detail");
			else
				self::Error($error);
		elseif ($error instanceof DetailedException)
			self::Error($error->getMessage());
		else
			self::Error($error . '.');
	}

	/**
	 * Show an error message.
	 * @param $message Error message to show
	 */
	protected static function Error(string $message): void {
	?>
		<p class=error><?= $message; ?></p>
	<?php
	}

	private static function SendHead(): void {
	?>

		<head>
			<meta charset=utf-8>
			<meta name=viewport content="width=device-width, initial-scale=1">
			<title><?= htmlspecialchars(self::$title); ?></title>
			<link rel=stylesheet href="/track7.css">
			<?php
			if (self::$rss && self::$rss->Title && self::$rss->URL) {
			?>
				<link rel=alternate type=application/rss+xml title="<?= self::$rss->Title; ?>" href="<?= self::$rss->URL; ?>">
			<?php
			}
			self::SendScripts();
			self::SendIcons();
			?>
		</head>
		<?php
	}

	private static function SendScripts(): void {
		if (count(self::$importmap)) {
		?>
			<script type="importmap"><?= json_encode(['imports' => self::$importmap]); ?></script>
		<?php
		}
		?>
		<script src="/prism.js" type="text/javascript"></script>
		<script src="/usermenu.js" type=module></script>
		<?php
		if (substr($_SERVER['SCRIPT_NAME'], 0, 10) == '/user/via/') {
		?>
			<script src=" /user/via/register.js" type="text/javascript"></script>
		<?php
		} elseif (file_exists(str_replace('.php', '.js', $_SERVER['SCRIPT_FILENAME']))) {
		?>
			<script src="<?= str_replace('.php', '.js', $_SERVER['SCRIPT_NAME']); ?>" type=module></script>
		<?php
		}
	}

	private static function SendIcons(): void {
		?>
		<link rel="apple-touch-icon" sizes=57x57 href="/apple-touch-icon-57x57.png">
		<link rel="apple-touch-icon" sizes=114x114 href="/apple-touch-icon-114x114.png">
		<link rel="apple-touch-icon" sizes=72x72 href="/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes=144x144 href="/apple-touch-icon-144x144.png">
		<link rel="apple-touch-icon" sizes=60x60 href="/apple-touch-icon-60x60.png">
		<link rel="apple-touch-icon" sizes=120x120 href="/apple-touch-icon-120x120.png">
		<link rel="apple-touch-icon" sizes=76x76 href="/apple-touch-icon-76x76.png">
		<link rel="apple-touch-icon" sizes=152x152 href="/apple-touch-icon-152x152.png">
		<link rel="apple-touch-icon" sizes=180x180 href="/apple-touch-icon-180x180.png">
		<link rel=icon type="image/png" sizes=192x192 href="/favicon-192x192.png">
		<link rel=icon type="image/png" sizes=96x96 href="/favicon-96x96.png">
		<link rel=icon type="image/png" sizes=16x16 href="/favicon-16x16.png">
		<link rel=icon type="image/png" sizes=32x32 href="/favicon-32x32.png">
		<meta name="msapplication-TileColor" content="#335577">
		<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<?php
	}

	private static function SendHeader(): void {
	?>
		<header>
			<a id=gohome href="/" title="track7 home"><img src="/images/home.png" alt="track7"></a>
			<div id=userstatus>
				<?php
				if (self::IsUserLoggedIn()) {
				?>
					<a id=whodat href="/user/<?= self::$user->Username; ?>/"><?= htmlspecialchars(self::$user->DisplayName); ?><?php if (self::$user->NotifyCount) echo '<span class=notifycount>' . self::$user->NotifyCount . '</span>'; ?><img class=avatar src="<?= self::$user->Avatar; ?>" alt=""></a>
				<?php
				} else {
				?>
					<a id=signin href="#signin">sign in</a>
				<?php
				}
				?>
			</div>
		</header>
		<?php
		if (self::IsUserLoggedIn()) {
		?>
			<div id=usermenu>
				<nav id=useractions>
					<a class=profile href="/user/<?= self::$user->Username; ?>/">profile</a>
					<a class=settings href="/user/settings.php">settings<?php if (self::$user->HasTransitionLogin) echo '<span class=notifycount>1</span>'; ?></a>
					<a class=messages href="/user/messages.php">messages<?php if (self::$user->UnreadMsgs) echo '<span class=notifycount>' . self::$user->UnreadMsgs . '</span>'; ?></a>
					<a id=logoutlink href="?logout">sign out</a>
				</nav>
			</div>
		<?php
		} else {
		?>
			<div id=loginmenu>
				<form id=signinform>
					sign in securely with your account from one of these sites:
					<div id=authchoices>
						<?php
						// TODO:  handle different continue and move t7auth to new class
						//$continue = isset($this->params['continue']) ? $this->params['continue'] : $_SERVER['REQUEST_URI'];
						$continue = $_SERVER['REQUEST_URI'];
						require_once 't7auth.php';
						require_once 'Parsedown.php';
						require_once 't7format.php';
						foreach (t7auth::GetAuthLinks($continue) as $name => $authurl) {
						?>
							<label class="<?= $name; ?>" title="sign in with your <?= $name; ?> account"><input type=radio name=login_url value="<?= htmlspecialchars($authurl); ?>"></label>
						<?php
						}
						?>
					</div>
					<div id=oldlogin>
						note:&nbsp; this is only for users who have already set up a password.
						<label>username: <input name=username maxlength=32></label>
						<label>password: <input name=password type=password></label>
					</div>
					<label for=rememberlogin><input type=checkbox id=rememberlogin name=remember> remember me</label>
					<button id=dologin disabled>choose site to sign in through</button>
				</form>
			</div>
		<?php
		}
	}

	private static function SendFooter() {
		?>
		<footer>
			<a href="/feed.rss" title="add track7 activity to your feed reader">rss</a>
			<a href="https://twitter.com/track7feed" title="follow track7 on twitter">twitter</a>
			<a href="https://github.com/misterhaan/track7.org/blob/master<?= $_SERVER['SCRIPT_NAME']; ?>?ts=2" title="view the php source for this page on github">php source</a>
			<a href="/privacy.php" title="view the privacy policy">privacy</a>
			<div id=copyright>© 1996 - <?= self::copyrightEndYear; ?> track7 — <a href="/fewrights.php">few rights reserved</a></div>
		</footer>
<?php
	}
}

/**
 * Pairing of a title and a URL
 */
class TitledLink {
	/**
	 * Link title
	 */
	public string $Title = '';
	/**
	 * Link URL
	 */
	public string $URL = '';

	/**
	 * Default constructor
	 * @param $title Link title
	 * @param $url Link URL
	 */
	public function __construct(string $title, string $url) {
		$this->Title = $title;
		$this->URL = $url;
	}
}
