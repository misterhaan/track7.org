<?php
  define('_HTML_SITE_TITLE', 'track7');

  class t7html {
    private $params;
    private $isopen = false;
    private $isclosed = false;

    public function t7html($params) {
      $this->params = $params;
    }

    public function Open($title) {
      if($this->isopen)
        return;
      $this->isopen = true;
      if(strpos($title, _HTML_SITE_TITLE) === false)
        $title .= ' - ' . _HTML_SITE_TITLE;
      header('X-Sven: look out for the fruits of life');
      header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang=en>
  <head>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <link rel=stylesheet href="/track7.css">
    <script src="/jquery-2.1.4.min.js" type="text/javascript"></script>
<?php
      if(isset($this->params['ko']) && $this->params['ko']) {
?>
    <script src="/knockout-3.3.0.js" type="text/javascript"></script>
<?php
      }
?>
    <script src="/track7.js" type="text/javascript"></script>
    <meta data-debug="<?php echo substr($_SERVER['SCRIPT_NAME'], 0, 10); ?>">
<?php
      if(substr($_SERVER['SCRIPT_NAME'], 0, 10) == '/user/via/') {
?>
    <script src="/user/via/register.js" type="text/javascript"></script>
<?php
      } elseif(file_exists(str_replace('.php', '.js', $_SERVER['SCRIPT_FILENAME']))) {
?>
    <script src="<?php echo str_replace('.php', '.js', $_SERVER['SCRIPT_NAME']); ?>" type="text/javascript"></script>
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
<?php
      if(isset($this->params['rss'])) {
        $rss = $this->params['rss'];
        if(isset($rss['title']) && isset($rss['url'])) {
?>
    <link rel=alternate type=application/rss+xml title="<?php echo $rss['title']; ?>" href="<?php echo $rss['url']; ?>">
<?php
        }
      }
?>
    <meta name="msapplication-TileColor" content="#335577">
    <meta name="msapplication-TileImage" content="/mstile-144x144.png">
  </head>
  <body class=text>
    <header>
      <a id=gohome href="/" title="track7 home"><img src="/images/home.png" alt="track7"></a>
      <div id=userstatus>
<?php
      global $user;
      if($user->IsLoggedIn()) {
?>
        <a id=whodat href="/user/<?php echo $user->Username; ?>/"><?php echo htmlspecialchars($user->DisplayName); ?><img class=avatar src="<?php echo $user->Avatar; ?>" alt=""></a>
<?php
      } else {
?>
        <a id=signin href="/user/login.php">sign in</a>
<?php
      }
?>
      </div>
    </header>
    <div id=usermenu>
<?php
      if($user->IsLoggedIn()) {
?>
      <nav id=useractions>
        <a href="/user/<?php echo $user->Username; ?>/">profile</a>
        <a href="/user/settings.php">settings</a>
        <a href="/user/message.php">messages</a>
        <a id=logoutlink href="?logout">sign out</a>
      </nav>
<?php
      } else {
?>
      <form id=signinform>
        sign in securely with your account from one of these sites:
        <div id=authchoices>
<?php
        $continue = isset($this->params['continue']) ? $this->params['continue'] : $_SERVER['REQUEST_URI'];
        foreach(t7auth::GetAuthLinks($continue) as $name => $auth) {
?>
          <label><input type=radio name=login_url value="<?php echo htmlspecialchars($auth['url']); ?>"> <img src="<?php echo htmlspecialchars($auth['img']); ?>" alt="<?php echo $name; ?>" title="sign in with your <?php echo $name; ?> account"></label>
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
<?php
      }
?>
    </div>
    <main role=main>
<?php

    }

    public function Close() {
      if(!$this->isopen || $this->isclosed)
        return;
      $this->isclosed = true;
?>
    </main>
    <footer>
      <a href="/feed.rss" title="add track7 activity to your feed reader">rss</a>
      <a href="https://twitter.com/track7feed" title="follow track7 on twitter">twitter</a>
      <a href="https://github.com/misterhaan/track7.org/blob/master<?php echo $_SERVER['SCRIPT_NAME']; ?>" title="view the php source for this page on github">php source</a>
      <div id=copyright>© 1996 - 2016 track7 — <a href="/fewrights.php">few rights reserved</a></div>
    </footer>
  </body>
</html>
<?php
    }
  }
?>
