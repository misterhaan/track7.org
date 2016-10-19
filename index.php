<?php
  define('MAXITEMS', 9);
  define('LONGDATEFMT', 'g:i a \o\n l F jS Y');
  define('FORUM_POSTS_PER_PAGE', 20);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html(['rss' => ['title' => 'unifeed', 'url' => '/feed.rss']]);
  $html->Open('track7');
?>
      <h1><img alt=track7 src="/images/track7.png"></h1>

      <section id=features>
        <nav>
          <a href="/bln/" title="read the blog">
            <img src="/bln/favicon.png" alt="">
            blog
          </a>
          <a href="/album/" title="see my photos">
            <img src="/album/favicon.png" alt="">
            photo album
          </a>
          <a href="/guides/" title="learn how i’ve done things">
            <img src="/guides/favicon.png" alt="">
            guides
          </a>
          <a href="/discgolf/" title="track disc golf scores">
            <img src="/discgolf/favicon.png" alt="">
            disc golf
          </a>
          <a href="/lego/" title="download instructions for custom lego models">
            <img src="/lego/favicon.png" alt="">
            lego models
          </a>
          <a href="/art/" title="see sketches and digital artwork">
            <img src="/art/favicon.png" alt="">
            visual art
          </a>
          <a href="/pen/" title="read short fiction and a poem">
            <img src="/pen/favicon.png" alt="">
            stories
          </a>
          <a href="/analogu/" title="download free software with source code">
            <img src="/analogu/favicon.png" alt="">
            software
          </a>
          <a href="/hb/" title="join or start conversations">
            <img src="/hb/favicon.png" alt="">
            forums
          </a>
<?php
  if($user->IsAdmin()) {
?>
          <a href="/tools/" title="administer track7">
            <img src="/favicon.png" alt="">
            tools
          </a>
<?php
  }
?>
        </nav>
      </section>
<?php
  // get last MAXITEMS from contributions, updates, posts, and comments
  $act = $update = $forum = $comment = $round = false;
  if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
    $act = $acts->fetch_object();
  if($updates = $db->query('select instant as posted, `change` as preview from track7_t7data.updates order by instant desc limit ' . MAXITEMS))
    $update = $updates->fetch_object();
  if($forums = $db->query('select p.id, p.number, p.thread, p.instant as posted, p.subject as title, p.post as preview, u.username, u.displayname from track7_t7data.hbposts as p left join track7_t7data.users as ou on ou.uid=p.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id order by instant desc limit ' . MAXITEMS))
    $forum = $forums->fetch_object();
  if($comments = $db->query('select \'comment\' as conttype, c.instant as posted, c.page as url, u.username, u.displayname, c.name as authorname, c.url as authorurl, substring_index(c.page, \'/\', -1) as title, c.comments as preview, 0 as hasmore from track7_t7data.comments as c left join track7_t7data.users as ou on ou.uid=c.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id where not (c.page like \'/bln/%\') and not (c.page like \'/guides/%\') and not (c.page like \'/album/%\') order by instant desc limit ' . MAXITEMS))
    $comment = $comments->fetch_object();
  if($rounds = $db->query('select r.id, r.instant as posted, c.name, r.player as authorname, \'\' as authorurl, u.username, u.displayname, r.roundtype, r.tees, r.score, r.comments from track7_t7data.dgrounds as r left join track7_t7data.dgcourses as c on c.id=r.courseid left join track7_t7data.users as ou on ou.uid=r.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id where r.entryuid is null or r.uid=0 order by posted desc limit ' . MAXITEMS))
    $round = $rounds->fetch_object();

  $items = 0;
  while($items < MAXITEMS && ($act || $update || $forum || $comment || $photo || $art || $round)) {
    if($act && (!$update || $act->posted > $update->posted) && (!$forum || $act->posted > $forum->posted) && (!$comment || $act->posted > $comment->posted) && (!$round || $act->posted > $round->posted)) {
      ShowContribution($act);
      $act = $acts->fetch_object();
    } elseif($update && (!$forum || $update->posted > $forum->posted) && (!$comment || $update->posted > $comment->posted) && (!$round || $update->posted > $round->posted)) {
      ShowUpdate($update);
      $update = $updates->fetch_object();
    } elseif($forum && (!$comment || $forum->posted > $comment->posted) && (!$round || $forum->posted > $round->posted)) {
      ShowForum($forum);
      $forum = $forums->fetch_object();
    } elseif($comment && (!$round || $comment->posted > $round->posted)) {
      ShowContribution($comment);
      $comment = $comments->fetch_object();
    } elseif($round) {
      ShowRound($round);
      $round = $rounds->fetch_object();
    }
    $items++;
  }
  $html->Close();

  function ShowContribution($act) {
?>
      <article class="activity <?php echo $act->conttype; ?>">
        <div class=whatwhen title="<?php echo $act->conttype; ?> at <?php echo t7format::LocalDate(LONGDATEFMT, $act->posted); ?>">
          <time datetime="<?php echo gmdate('c', $act->posted); ?>"><?php echo t7format::SmartDate($act->posted); ?></time>
        </div>
        <div>
          <h2><?php echo ContributionPrefix($act->conttype); ?><a href="<?php echo $act->url; ?>"><?php echo $act->title; ?></a> by <?php echo AuthorLink($act); ?></h2>
          <div class=summary>
            <?php echo $act->preview; ?>
<?php
    if($act->hasmore) {
?>
            <p class=readmore><a href="<?php echo htmlspecialchars($act->url); ?>">⇨ read more</a></p>
<?php
    }
?>
          </div>
        </div>
      </article>
<?php
  }

  function ShowUpdate($update) {
?>
      <article class="activity update">
        <div class=whatwhen title="site update at <?php echo t7format::LocalDate(LONGDATEFMT, $update->posted); ?>">
          <time datetime="<?php echo gmdate('c', $update->posted); ?>"><?php echo t7format::SmartDate($update->posted); ?></time>
        </div>
        <div>
          <h2>track7 update by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></h2>
          <div class=summary><p><?php echo $update->preview; ?></p></div>
        </div>
      </article>
<?php
  }

  function ShowForum($forum) {
    $forum->url = '/hb/thread' . $forum->thread . '/';
    if($forum->number - 1 > FORUM_POSTS_PER_PAGE)
      $forum->url .= 'skip=' . floor(($forum->number - 1) / FORUM_POSTS_PER_PAGE) * FORUM_POSTS_PER_PAGE;
    $forum->url .= '#p' . $forum->id;
    $forum->authorurl = false;
    $forum->authorname = 'anonymous';
?>
      <article class="activity forum">
        <div class=whatwhen title="forum post at <?php echo t7format::LocalDate(LONGDATEFMT, $forum->posted); ?>">
          <time datetime="<?php echo gmdate('c', $forum->posted); ?>"><?php echo t7format::SmartDate($forum->posted); ?></time>
        </div>
        <div>
          <h2><a href="<?php echo $forum->url; ?>"><?php echo $forum->title; ?></a> by <?php echo AuthorLink($forum); ?></h2>
          <?php echo $forum->preview; ?>
        </div>
      </article>
<?php
  }

  function ShowRound($round) {
?>
      <article class="activity round">
        <div class=whatwhen title="disc golf round at <?php echo t7format::LocalDate(LONGDATEFMT, $round->posted); ?>">
          <time datetime="<?php echo gmdate('c', $round->posted); ?>"><?php echo t7format::SmartDate($round->posted); ?></time>
        </div>
        <div>
          <h2><a href="/discgolf/rounds.php?id=<?php echo $round->id; ?>">disc golf round at <?php echo $round->name; ?></a> by <?php echo AuthorLink($round); ?></h2>
          <p>
            scored <?php echo $round->score; ?> in a <?php echo $round->roundtype; ?> round<?php echo $round->tees ? ' from the ' . $round->tees . ' tees.' : '.'; ?>
          </p>
          <p class=readmore><a href="/discgolf/rounds.php?id=<?php echo $round->id; ?>">⇨ read more</a></p>
        </div>
      </article>
<?php
  }

  function ContributionPrefix($type) {
    switch($type) {
      case 'comment':
        return 'comment on ';
    }
    return '';
  }

  function AuthorLink($act) {
    if($act->username) {
      if(!$act->displayname)
        $act->displayname = $act->username;
      return '<a href="/user/' . htmlspecialchars($act->username) . '/" title="view ' . htmlspecialchars($act->displayname) . '’s profile">' . htmlspecialchars($act->displayname) . '</a>';
    }
    if($act->authorurl)
      return '<a href="'. htmlspecialchars($act->authorurl) . '">' . htmlspecialchars($act->authorname) . '</a>';
    return htmlspecialchars($act->authorname);
  }
?>
