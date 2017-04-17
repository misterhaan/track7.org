<?php
  if(isset($_GET['login'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/.dbinfo.track7.php';
    $olddb = new mysqli(_DB_HOST, _DB_USER, _DB_PASS, _DB_NAME);
    $olddb->real_query('set names \'utf8\'');
    $olddb->set_charset('utf8');

    $u = new t7user($_GET['login']);
    if(!$u->IsLoggedIn())
      $u = new oldUser($_GET['login']);
    if($u->IsLoggedIn()) {
      $u->DisplayName = htmlspecialchars($u->DisplayName);
      $stats = $u->GetStats();
      $html = new t7html([]);
      $html->Open($u->DisplayName);
?>
      <header class=profile>
        <img class=avatar src="<?php echo htmlspecialchars($u->Avatar); ?>" alt="">
        <div>
          <h1>
            <?php echo $u->DisplayName; ?>
<?php
      if($u->Fan) {
?>
            <img class=friend src="/images/friend.png" alt="☆" title="<?php echo $u->DisplayName; ?> is your friend">
<?php
      }
?>
          </h1>
          <p><?php echo $u->GetLevelName(); ?>, joined <time datetime="<?php echo gmdate('c', $stats->registered); ?>" title="<?php echo t7format::LocalDate('g:i a \o\n l F jS Y', $stats->registered); ?>"><?php echo t7format::HowLongAgo($stats->registered); ?> ago</time></p>
        </div>
      </header>
<?php
      if(!is_a($u, 'oldUser')) {
?>
      <nav class=actions>
<?php
        if($u->ID != $user->ID) {
?>
        <a class=message title="send <?php echo $u->DisplayName; ?> a private message" href="/user/messages.php#!to=<?php echo htmlspecialchars($u->Username); ?>">send message</a>
<?php
        }
        if($user->IsLoggedIn())
          if($u->ID == $user->ID) {
?>
        <a class=edit title="edit your profile" href="/user/settings.php">edit profile</a>
<?php
          } elseif($u->Fan) {
?>
        <a class=removefriend title="remove <?php echo $u->DisplayName; ?> from your friends" href="/user/?ajax=removefriend&amp;friend=<?php echo $u->ID; ?>">remove friend</a>
<?php
          } else {
?>
        <a class=addfriend title="add <?php echo $u->DisplayName; ?> as a friend" href="/user/?ajax=addfriend&amp;friend=<?php echo $u->ID; ?>">add friend</a>
<?php
          }
?>
      </nav>
<?php
      }
      if(count($links = $u->GetContactLinks())) {
?>
      <section id=contact>
<?php
        foreach($links as $link) {
?>
        <a href="<?php echo htmlspecialchars($link['url']); ?>" title="<?php echo $link['title']; ?>"><img src="/images/contact/<?php echo $link['type']; ?>.png" alt="<?php echo $link['type']; ?>"></a>
<?php
        }
?>
      </section>
<?php
      }
      if($stats->fans || $stats->comments || $stats->replies) {
?>
      <section id=rank>
        <header>rankings</header>
        <ul>
<?php
        if($stats->fans) {
?>
          <li>#<?php echo Rank('fans', $stats->fans); ?> in fans with <?php echo $stats->fans; ?></li>
<?php
        }
        if($stats->comments) {
?>
          <li>#<?php echo Rank('comments', $stats->comments); ?> in <a href="/comments.php?user=<?php echo $u->Username; ?>" title="view all of <?php echo $u->Username; ?>’s comments">comments</a> with <?php echo $stats->comments; ?></li>
<?php
        }
        if($stats->replies) {
?>
          <li>#<?php echo Rank('replies', $stats->replies); ?> in <a href="/user/<?php echo $u->Username; ?>/replies" title="view all of <?php echo $u->Username; ?>’s forum posts">forum posts</a> with <?php echo $stats->replies; ?></li>
<?php
        }
?>
        </ul>
      </section>
<?php
      }
      if($acts = $db->query('select conttype, posted, url, title from contributions where author=\'' . +$u->ID . '\' order by posted desc limit 12'))
      	if($acts->num_rows) {
?>
      <ol id=activity>
<?php
        	while($act = $acts->fetch_object()) {
?>
        <li class=<?php echo $act->conttype; ?>><?php echo ActionWords($act->conttype); ?> <a href="<?php echo $act->url; ?>"><?php echo $act->title; ?></a> <time datetime="<?php echo gmdate('c', $act->posted); ?>" title="<?php echo t7format::LocalDate('g:i a \o\n l F jS Y', $act->posted); ?>"><?php echo t7format::HowLongAgo($act->posted); ?> ago</time></li>
<?php
        	}
?>
      </ol>
<?php
      	} else {
?>
      <p><?php echo $u->DisplayName; ?> hasn’t posted anything to track7 yet.</p>
<?php
      	}
      $html->Close();
    } else  // user not found; go to user index
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');
  } else  // user not specified; go to user index
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');

  /**
   * Count how many users have at least the specified value in a certain stat.
   * @param string $stat Field name of the stat to check
   * @param integer $value User's value for the stat
   * @return integer|string value to display as the rank
   */
  function Rank($stat, $value) {
    global $db, $olddb;
    switch($stat) {
      case 'fans':
      case 'comments':
      case 'replies':
        if($r = $db->query('select count(1) as rank from users_stats where ' . $stat . '>=' . +$value))
          if($r = $r->fetch_object())
            return $r->rank;
        break;
    }
    return '<em title=unknown>?</em>';
  }

  /**
   * Get the action words for a contribution type.
   * @param string $type Contribution type
   * @return string Action words (defaults to [type]ed) if unknown type
   */
  function ActionWords($type) {
    switch($type) {
      case 'comment':
        return 'commented on';
      case 'guide':
        return 'posted guide';
    }
    if(substr($type, -1) == 'e')
      return $type . 'd';
    return $type . 'ed';
  }

  /**
   * Version of the profile page portion of t7user that uses the old database
   * instead.
   * @author misterhaan
   */
  class oldUser {
    private $found = false;
    public $ID = false;
    public $Username = false;
    public $DisplayName = false;
    public $Avatar = false;
    private $Friend = false;
    public $Fan = false;

    public function oldUser($login) {
      global $olddb, $user;
      if($u = $olddb->query('select u.uid, u.login, p.avatar, fa.fanuid, fr.frienduid from users as u left join userprofiles as p on p.uid=u.uid left join userfriends as fr on fr.fanuid=u.uid and fr.frienduid=\'' . $user->OldID() . '\' left join userfriends as fa on fa.frienduid=u.uid and fa.fanuid=\'' . $user->OldID() . '\' where login=\'' . $olddb->escape_string($login) . '\''))
        if($u = $u->fetch_object()) {
          $this->found = true;
          $this->ID = $u->uid;
          $this->Username = $u->login;
          $this->DisplayName = $u->login;
          $this->Avatar = $u->avatar ? '/user/avatar/' . $u->login . '.' . $u->avatar : '/images/user.jpg';
          $this->Friend = $u->frienduid;
          $this->Fan = $u->fanuid;
        }
    }

    public function IsLoggedIn() {
      return $this->found;
    }

    public function GetStats() {
      global $olddb;
      if($s = $olddb->query('select since as registered, fans, comments from userstats where uid=\'' . +$this->ID . '\''))
        if($s = $s->fetch_object())
          return $s;
      return false;
    }

    public function GetLevelName() {
      return 'old';
    }

    public function GetContactLinks() {
      global $olddb, $user;
      $links = [];
      if($c = $olddb->query('select email, website, twitter, steam, flags from usercontact where uid=\'' . +$this->ID . '\' limit 1'))
        if($c = $c->fetch_object()) {
          if($c->email && ($c->flags & 1 || $user->IsAdmin()))
            $links[] = ['type' => 'email', 'url' => 'mailto:' . $c->email, 'title' => 'send ' . $this->DisplayName . ' an e-mail'];
          if($c->website)
            $links[] = ['type' => 'www', 'url' => $c->website, 'title' => 'visit ' . $this->DisplayName . '’s website'];
          if($c->twitter)
            $links[] = ['type' => 'twitter', 'url' => self::ExpandProfileLink($c->twitter, 'twitter'), 'title' => 'view ' . $this->DisplayName . '’s twitter profile'];
          if($c->steam)
            $links[] = ['type' => 'steam', 'url' => self::ExpandProfileLink($c->steam, 'steam'), 'title' => 'view ' . $this->DisplayName . '’s steam community profile'];
        }
      return $links;
    }

    public function OldID() {
      return $this->ID;
    }
  }
?>
