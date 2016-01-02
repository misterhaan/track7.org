<?php
  define('MAXITEMS', 9);
  define('LONGDATEFMT', 'g:i a \o\n l F jS Y');
  define('FORUM_POSTS_PER_PAGE', 20);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
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
          <a href="/art/lego/" title="download instructions for custom lego models">
            <img src="/art/lego/favicon.png" alt="">
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
  // get last MAXITEMS from contributions, updates, posts, comments, and photos
  $act = $update = $forum = $comment = $photo = $guide = $art = $round = false;
  if($acts = $db->query('select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author order by c.posted desc limit ' . MAXITEMS))
    $act = $acts->fetch_object();
  if($updates = $db->query('select instant as posted, `change` as preview from track7_t7data.updates order by instant desc limit ' . MAXITEMS))
    $update = $updates->fetch_object();
  if($forums = $db->query('select p.id, p.number, p.thread, p.instant as posted, p.subject as title, p.post as preview, u.username, u.displayname from track7_t7data.hbposts as p left join track7_t7data.users as ou on ou.uid=p.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id order by instant desc limit ' . MAXITEMS))
    $forum = $forums->fetch_object();
  if($comments = $db->query('select \'comment\' as conttype, c.instant as posted, c.page as url, u.username, u.displayname, c.name as authorname, c.url as authorurl, substring_index(c.page, \'/\', -1) as title, c.comments as preview, 0 as hasmore from track7_t7data.comments as c left join track7_t7data.users as ou on ou.uid=c.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id where not (c.page like \'/bln/%\') order by instant desc limit ' . MAXITEMS))
    $comment = $comments->fetch_object();
  if($photos = $db->query('select added as posted, id, caption as title from track7_t7data.photos order by posted desc limit ' . MAXITEMS))
    $photo = $photos->fetch_object();
  if($guides = $db->query('select g.id, g.dateadded as posted, g.title, g.description as preview, u.username, u.displayname from track7_t7data.guides as g left join track7_t7data.users as ou on ou.uid=g.author left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id order by posted desc limit ' . MAXITEMS))
    $guide = $guides->fetch_object();
  if($arts = $db->query('select id, name as title, `type`, adddate as posted from track7_t7data.art order by posted desc limit ' . MAXITEMS))
    $art = $arts->fetch_object();
  if($rounds = $db->query('select r.id, r.instant as posted, c.name, r.player as authorname, \'\' as authorurl, u.username, u.displayname, r.roundtype, r.tees, r.score, r.comments from track7_t7data.dgrounds as r left join track7_t7data.dgcourses as c on c.id=r.courseid left join track7_t7data.users as ou on ou.uid=r.uid left join transition_users as tu on tu.olduid=ou.uid left join users as u on u.id=tu.id where r.entryuid is null or r.uid=0 order by posted desc limit ' . MAXITEMS))
    $round = $rounds->fetch_object();

  $items = 0;
  while($items < MAXITEMS && ($act || $update || $forum || $comment || $photo || $guide || $art || $round)) {
    if($act && (!$update || $act->posted > $update->posted) && (!$forum || $act->posted > $forum->posted) && (!$comment || $act->posted > $comment->posted) && (!$photo || $act->posted > $photo->posted) && (!$guide || $act->posted > $guide->posted) && (!$art || $act->posted > $art->posted) && (!$round || $act->posted > $round->posted)) {
      ShowContribution($act);
      $act = $acts->fetch_object();
    } elseif($update && (!$forum || $update->posted > $forum->posted) && (!$comment || $update->posted > $comment->posted) && (!$photo || $update->posted > $photo->posted) && (!$guide || $update->posted > $guide->posted) && (!$art || $update->posted > $art->posted) && (!$round || $update->posted > $round->posted)) {
      ShowUpdate($update);
      $update = $updates->fetch_object();
    } elseif($forum && (!$comment || $forum->posted > $comment->posted) && (!$photo || $forum->posted > $photo->posted) && (!$guide || $forum->posted > $guide->posted) && (!$art || $forum->posted > $art->posted) && (!$round || $forum->posted > $round->posted)) {
      ShowForum($forum);
      $forum = $forums->fetch_object();
    } elseif($comment && (!$photo || $comment->posted > $photo->posted) && (!$guide || $comment->posted > $guide->posted) && (!$round || $comment->posted > $round->posted)) {
      ShowContribution($comment);
      $comment = $comments->fetch_object();
    } elseif($photo && (!$guide || $photo->posted > $guide->posted) && (!$art || $photo->posted > $art->posted) && (!$round || $photo->posted > $round->posted)) {
      ShowPhoto($photo);
      $photo = $photos->fetch_object();
    } elseif($guide && (!$art || $guide->posted > $art->posted) && (!$round || $guide->posted > $round->posted)) {
      ShowGuide($guide);
      $guide = $guides->fetch_object();
    } elseif($art && (!$round || $art->posted > $round->posted)) {
      ShowArt($art);
      $art = $arts->fetch_object();
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

  function ShowPhoto($photo) {
?>
      <article class="activity photo">
        <div class=whatwhen title="photo at <?php echo t7format::LocalDate(LONGDATEFMT, $photo->posted); ?>">
          <time datetime="<?php echo gmdate('c', $photo->posted); ?>"><?php echo t7format::SmartDate($photo->posted); ?></time>
        </div>
        <div>
          <h2><a href="/album/photo=<?php echo $photo->id; ?>"><?php echo $photo->title; ?></a> by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></h2>
          <p><a href="/album/photo=<?php echo $photo->id; ?>"><img class=photothumb src="/album/photos/<?php echo $photo->id; ?>.jpg" alt=""></a></p>
          <p class=readmore><a href="/album/photo=<?php echo $photo->id; ?>">⇨  see larger</a></p>
        </div>
      </article>
<?php
  }

  function ShowGuide($guide) {
?>
      <article class="activity guide">
        <div class=whatwhen title="guide at <?php echo t7format::LocalDate(LONGDATEFMT, $guide->posted); ?>">
          <time datetime="<?php echo gmdate('c', $guide->posted); ?>"><?php echo t7format::SmartDate($guide->posted); ?></time>
        </div>
        <div>
          <h2><a href="/guides/<?php echo $guide->id; ?>"><?php echo $guide->title; ?></a> by <?php echo AuthorLink($guide); ?></h2>
          <p><?php echo $guide->preview; ?></p>
          <p class=readmore><a href="/guides/<?php echo $guide->id; ?>">⇨  read more</a></p>
        </div>
      </article>
<?php
  }

  function ShowArt($art) {
    if($art->type == 'digital')
      $art->type = 'digital art';
    if(!$art->title)
      $art->title = str_replace('-', ' ', $art->id);
?>
      <article class="activity art">
        <div class=whatwhen title="<?php echo $art->type; ?> at <?php echo t7format::LocalDate(LONGDATEFMT, $art->posted); ?>">
          <time datetime="<?php echo gmdate('c', $art->posted); ?>"><?php echo t7format::SmartDate($art->posted); ?></time>
        </div>
        <div>
          <h2><a href="/output/gfx/sketch.php#<?php echo $art->id; ?>"><?php echo $art->title; ?></a> by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></h2>
          <p><a href="/output/gfx/sketch.php#<?php echo $art->id; ?>"><img class=photothumb src="/output/gfx/<?php echo $art->id; ?>-prev.png" alt=""></a></p>
          <p class=readmore><a href="/output/gfx/sketch.php#<?php echo $art->id; ?>">⇨  see larger</a></p>
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
