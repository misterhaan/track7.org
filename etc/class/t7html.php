<?php
class t7html {
	const SITE_TITLE = 'track7';

	private $params = [];
	private $isopen = false;
	private $isclosed = false;

	public function __construct($params = []) {
		$this->params = $params;
	}

	public function Open($title) {
		if ($this->isopen)
			return;
		$this->isopen = true;
		if (strpos($title, self::SITE_TITLE) === false)
			$title .= ' - ' . self::SITE_TITLE;
		header('X-Sven: look out for the fruits of life');
		header('Content-Type: text/html; charset=utf-8');
?>
		<!DOCTYPE html>
		<html lang=en>

		<head>
			<meta charset=utf-8>
			<meta name=viewport content="width=device-width, initial-scale=1">
			<title><?= $title; ?></title>
			<link rel=stylesheet href="/track7.css">
			<script src="/jquery-3.7.1.min.js" type="text/javascript"></script>
			<script src="/autosize.min.js" type="text/javascript"></script>
			<?php
			if (isset($this->params['vue']) && $this->params['vue'])
				if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vue.js')) {
			?>
				<script src="/vue.js" type="text/javascript"></script>
			<?php
				} else {
			?>
				<script src="/vue.min.js" type="text/javascript"></script>
			<?php
				}
			?>
			<script src="/prism.js" type="text/javascript"></script>
			<script src="/track7.js" type="text/javascript"></script>
			<?php
			if (substr($_SERVER['SCRIPT_NAME'], 0, 10) == '/user/via/') {
			?>
				<script src="/user/via/register.js" type="text/javascript"></script>
			<?php
			} elseif (file_exists(str_replace('.php', '.js', $_SERVER['SCRIPT_FILENAME']))) {
			?>
				<script src="<?= str_replace('.php', '.js', $_SERVER['SCRIPT_NAME']); ?>" type="module"></script>
			<?php
			}
			?>
			<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
			<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
			<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
			<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
			<link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
			<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
			<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
			<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
			<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
			<link rel=icon type="image/png" sizes="192x192" href="/favicon-192x192.png">
			<link rel=icon type="image/png" sizes="96x96" href="/favicon-96x96.png">
			<link rel=icon type="image/png" sizes="16x16" href="/favicon-16x16.png">
			<link rel=icon type="image/png" sizes="32x32" href="/favicon-32x32.png">
			<meta name="msapplication-TileColor" content="#335577">
			<meta name="msapplication-TileImage" content="/mstile-144x144.png">
		</head>

		<body class=<?= isset($this->params['bodytype']) ? $this->params['bodytype'] : 'text'; ?>>
			<header>
				<a id=gohome href="/" title="track7 home"><img src="/images/home.png" alt="track7"></a>
				<div id=userstatus>
					<?php
					global $user;
					if ($user->IsLoggedIn()) {
					?>
						<a id=whodat href="/user/<?= $user->Username; ?>/"><?= htmlspecialchars($user->DisplayName); ?><?php if ($user->NotifyCount) echo '<span class=notifycount>' . $user->NotifyCount . '</span>'; ?><img class=avatar src="<?= $user->Avatar; ?>" alt=""></a>
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
			if ($user->IsLoggedIn()) {
			?>
				<div id=usermenu>
					<nav id=useractions>
						<a class=profile href="/user/<?= $user->Username; ?>/">profile</a>
						<a class=settings href="/user/settings.php">settings<?php if ($user->SettingsAlerts) echo '<span class=notifycount>' . $user->SettingsAlerts . '</span>'; ?></a>
						<a class=messages href="/user/messages.php">messages<?php if ($user->UnreadMsgs) echo '<span class=notifycount>' . $user->UnreadMsgs . '</span>'; ?></a>
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
							$continue = isset($this->params['continue']) ? $this->params['continue'] : $_SERVER['REQUEST_URI'];
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
			?>
			<main>
			<?php
		}

		public function Close() {
			if (!$this->isopen || $this->isclosed)
				return;
			$this->isclosed = true;
			?>
			</main>
			<footer>
				<a href="https://twitter.com/track7feed" title="follow track7 on twitter">twitter</a>
				<a href="https://github.com/misterhaan/track7.org/blob/master<?= $_SERVER['SCRIPT_NAME']; ?>?ts=2" title="view the php source for this page on github">php source</a>
				<a href="/privacy.php" title="view the privacy policy">privacy</a>
				<div id=copyright>© 1996 - 2025 track7 — <a href="/fewrights.php">few rights reserved</a></div>
			</footer>
		</body>

		</html>
<?php
		}
	}
