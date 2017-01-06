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
    <script src="/jquery-3.1.0.min.js" type="text/javascript"></script>
    <script src="/autosize.min.js" type="text/javascript"></script>
<?php
      if(isset($this->params['ko']) && $this->params['ko']) {
?>
    <script src="/knockout-3.3.0.js" type="text/javascript"></script>
<?php
      }
?>
    <script src="/prism.js" type="text/javascript"></script>
    <script src="/track7.js" type="text/javascript"></script>
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
  <body class=<?php echo isset($this->params['bodytype']) ? $this->params['bodytype'] : 'text'; ?>>
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
<?php
      if($user->IsLoggedIn()) {
?>
    <div id=usermenu>
      <nav id=useractions>
        <a href="/user/<?php echo $user->Username; ?>/">profile</a>
        <a href="/user/settings.php">settings</a>
        <a href="/user/messages.php">messages<?php if($user->UnreadMsgs) echo '(' . $user->UnreadMsgs . ')'; ?></a>
        <a id=logoutlink href="?logout">sign out</a>
      </nav>
<?php
      } else {
?>
    <div id=loginmenu>
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

    /**
     * show a 5-star voting apparatus for this thing.
     * @param string $type prefix of the _votes table to use
     * @param unknown $key typically the id of the thing being voted on
     * @param integer $vote current vote in number of stars (1 through 5)
     */
    public function ShowVote($type, $key, $vote) {
      echo '<span id=vote ';
      if($vote >= 1)
        echo 'class=voted ';
      echo 'data-type=' . $type . ' data-key=' . $key . ' data-vote=1 title="one star — bad"><span ';
      if($vote >= 2)
        echo 'class=voted ';
      echo 'data-vote=2 title="two stars — below average"><span ';
      if($vote >= 3)
        echo 'class=voted ';
      echo 'data-vote=3 title="three stars — average"><span ';
      if($vote >= 4)
        echo 'class=voted ';
      echo 'data-vote=4 title="four stars — above average"><span ';
      if($vote >= 5)
        echo 'class=voted ';
      echo 'data-vote=5 title="five stars — great"></span></span></span></span></span>';
    }

    /**
     * show comments and form for adding comments.
     * @param string $name display name of the type of thing the comments apply to
     * @param string $type prefix of the _comments table to use
     * @param unknown $key typically the id of the thing the comments apply to
     */
    public function ShowComments($name, $type, $key) {
      global $user;
?>
      <section id=comments>
        <h2>comments</h2>
        <p data-bind="visible: error(), text: error"></p>
        <p data-bind="visible: !loadingComments() && comments().length == 0">
          there are no comments on this <?php echo $name; ?> so far.  you could be the first!
        </p>
        <!-- ko foreach: comments -->
        <section class=comment>
          <div class=userinfo>
            <div class=username data-bind="visible: !username && !contacturl, text: name"></div>
            <div class=username data-bind="visible: !username && contacturl"><a data-bind="text: name, attr: {href: contacturl}"></a></div>
            <div class=username data-bind="visible: username">
              <a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a>
              <img data-bind="visible: friend, attr: {title: (displayname || username) + ' is your friend'}" alt="*" src="/images/friend.png">
            </div>
            <a data-bind="visible: avatar"><img class=avatar alt="" data-bind="attr: {src: avatar}"></a>
            <div class=userlevel data-bind="visible: level, text:level"></div>
          </div>
          <div class=comment>
            <header>posted <time data-bind="text: posted.display, attr: {datetime: posted.datetime}"></time></header>
            <div class=content data-bind="visible: !editing(), html: html"></div>
            <div class="content edit" data-bind="visible: editing">
              <textarea data-bind="value: markdown"></textarea>
            </div>
            <footer data-bind="visible: canchange">
              <a class="okay action" data-bind="visible: editing(), click: $parent.SaveComment" href="/comments.php?ajax=save">save</a>
              <a class="cancel action" data-bind="visible: editing(), click: $parent.UneditComment" href="#">cancel</a>
              <a class="edit action" data-bind="visible: !editing(), click: $parent.EditComment" href="/comments.php?ajax=edit">edit</a>
              <a class="del action" data-bind="visible: !editing(), click: $parent.DeleteComment" href="/comments.php?ajax=delete">delete</a>
            </footer>
          </div>
        </section>

        <!-- /ko -->

        <form id=addcomment data-type=<?php echo $type; ?> data-key=<?php echo $key; ?>>
<?php
      if($user->IsLoggedIn()) {
?>
          <label title="you are signed in, so your comment will post with your avatar and a link to your profile">
            <span class=label>name:</span>
            <span class=field><a href="/user/<?php echo $user->Username; ?>/"><?php echo htmlspecialchars($user->DisplayName); ?></a></span>
          </label>
<?php
      } else {
?>
          <label title="please sign in or enter a name so we know what to call you">
            <span class=label>name:</span>
            <span class=field><input id=authorname></span>
          </label>
          <label title="enter a website, web page, or e-mail address if you want people to be able to find you">
            <span class=label>contact:</span>
            <span class=field><input id=authorcontact></span>
          </label>
<?php
      }
?>
          <label class=multiline title="enter your comments using markdown">
            <span class=label>comment:</span>
            <span class=field><textarea id=newcomment></textarea></span>
          </label>
          <button id=postcomment>post comment</button>
        </form>
      </section>
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
      <div id=copyright>© 1996 - 2017 track7 — <a href="/fewrights.php">few rights reserved</a></div>
    </footer>
  </body>
</html>
<?php
    }
  }
?>
