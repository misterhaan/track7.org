<?php
require_once 'environment.php';

/**
 * HTML Page for track7
 */
abstract class Page extends Responder {
	private const siteTitle = 'track7';
	private const copyrightEndYear = 2025;

	protected static string $title = self::siteTitle;
	protected static string $bodytype = 'text';
	protected static array $importmap = [
		'jquery' => '/jquery-3.7.1.min.js',
		'autosize' => '/autosize.esm.js',
		'popup' => '/popup.js',
		'user' => '/user.js',
		'comment' => '/comment.js',
		'tag' => '/tag.js',
		'vote' => '/vote.js',
		'validate' => '/validate.js'
	];
	protected static array $params = [];

	/**
	 * Generic constructor sets a title and starts output.
	 * @param $title Title of the page to show on the browser tab.
	 */
	public function __construct(string $title) {
		self::Initialize($title);
		self::Send();
	}

	private static function Initialize(string $title): void {
		self::$importmap['vue'] = file_exists($_SERVER['DOCUMENT_ROOT'] . '/vue.esm-browser.js') ? '/vue.esm-browser.js' : '/vue.esm-browser.prod.js';
		if (strpos($title, self::siteTitle) === false)
			self::$title = $title . ' - ' . self::siteTitle;
		else
			self::$title = $title;
	}

	/**
	 * Redirect somewhere within the subdirectory of the current script.  Must be called from constructor to work.  Halts the script.
	 * @param $relativePath Relative path the redirect should target.  Default is the index page.
	 */
	protected static function Redirect(string $relativePath = ''): void {
		require_once 'formatUrl.php';
		if (substr($relativePath, 0, 1) != '/')
			$relativePath = dirname($_SERVER['SCRIPT_NAME']) . '/' . $relativePath;
		header('Location: ' . FormatURL::FullUrl($relativePath));
		die;
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
					} catch (mysqli_sql_exception $mse) {
						self::DetailedError(DetailedException::FromMysqliException('database error.', $mse));
					} catch (Exception $e) {
						self::DetailedError($e->getMessage());
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
		<nav class=tagcloud data-plural-name="<?= $pluralName; ?>"></nav>
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

	protected static function ShowVoteWidget(int $post, int $vote, string $prompt): void {
	?>
		<p>
			<?= $prompt; ?>
			<span id=vote <?= $vote >= 1 ? 'class=voted ' : ''; ?>data-post=<?= $post; ?> data-vote=1 title="one star — bad">
				<span <?= $vote >= 2 ? 'class=voted ' : ''; ?>data-vote=2 title="two stars — below average">
					<span <?= $vote >= 3 ? 'class=voted ' : ''; ?>data-vote=3 title="three stars — average">
						<span <?= $vote >= 4 ? 'class=voted ' : ''; ?>data-vote=4 title="four stars — above average">
							<span <?= $vote >= 5 ? 'class=voted ' : ''; ?>data-vote=5 title="five stars — great">
							</span>
						</span>
					</span>
				</span>
			</span>
		</p>
	<?php
	}

	protected static function ShowRating(float $rating, int $votes): void {
	?>
		<span class=rating data-stars=<?= round($rating * 2) / 2; ?> title="rated <?= $rating; ?> stars by <?= $votes == 0 ? 'nobody' : ($votes == 1 ? '1 person' : $votes . ' people'); ?>"></span>
	<?php
	}

	/**
	 * End the page with a not found error.
	 * @param $title Not found error title
	 * @param $body HTML body of the not found error message
	 */
	protected static function NotFound(string $title = '404 bad guess', string $body = '') {
		header('HTTP/1.0 404 Not Found');
		self::Initialize($title);
		if (!$body)
			$body = '			<p>
				sorry, that’s not a thing.  if you followed a link and expected to find
				a thing, you should tell the owner of the link there’s nothing here so
				they can fix it.  if the link was from track7,
				<a href="/user/messages.php#!to=misterhaan">tell misterhaan</a>.  if
				you were just making stuff up, you might do better with this google
				search of everything on track7:
			</p>

			<form id=googletrack7 action="https://www.google.com/search">
				<label>
					<span class=label>search:</span>
					<span class=field><input type=search name=q placeholder="search track7 with google"></span>
				</label>
				<input type=hidden name=sitesearch value="track7.org">
				<button>search</button>
			</form>';
		self::Send("<h1>$title</h1>$body");
		die;
	}

	/**
	 * Show a detailed error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected static function DetailedError(mixed $error, ?string $detail = null): void {
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
			<link rel=stylesheet href=/theme/track7.css>
			<?php
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
		<script src="/theme.js" type="module"></script>
		<script src="/user.js" type=module></script>
		<?php
		if (file_exists(str_replace('.php', '.js', $_SERVER['SCRIPT_FILENAME']))) {
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
			<button id=theme-toggle title="change theme"></button>
			<div id=userstatus>
				<?php
				if (self::IsUserLoggedIn()) {
				?>
					<a id=whodat href="/user/<?= self::$user->Username; ?>/" data-level=<?= self::$user->Level . '-' . UserLevel::Name(self::$user->Level); ?>><?= htmlspecialchars(self::$user->DisplayName); ?><?php if (self::$user->UnreadMsgs) echo '<span class=notifycount>' . self::$user->UnreadMsgs . '</span>'; ?><img class=avatar src="<?= self::$user->Avatar; ?>" alt=""></a>
				<?php
				} else {
				?>
					<a id=signin href=#signin>sign in</a>
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
					<a class=settings href="/user/settings.php">settings</a>
					<a class=messages href="/user/messages.php">messages<?php if (self::$user->UnreadMsgs) echo '<span class=notifycount>' . self::$user->UnreadMsgs . '</span>'; ?></a>
					<a id=logoutlink href="/api/user.php/logout">sign out</a>
				</nav>
			</div>
		<?php
		} else {
		?>
			<div id=loginmenu></div>
		<?php
		}
	}

	private static function SendFooter() {
		?>
		<footer>
			<a href="https://twitter.com/track7feed" title="follow track7 on twitter">twitter</a>
			<a href="https://github.com/misterhaan/track7.org/blob/master<?= $_SERVER['SCRIPT_NAME']; ?>" title="view the php source for this page on github">php source</a>
			<a href="/privacy.php" title="view the privacy policy">privacy</a>
			<div id=copyright>© 1996 - <?= self::copyrightEndYear; ?> track7 — <a href="/fewrights.php">few rights reserved</a></div>
		</footer>
<?php
	}
}

class PrevNext {
	public ?TitledLink $Prev = null;
	public ?TitledLink $Next = null;
}
