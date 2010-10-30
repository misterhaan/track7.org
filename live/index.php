<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  define('MAXITEMS', 9);
  define('LONGDATEFMT', 'g:i a \o\n l F jS Y');

  $page->AddFeed('track7', '/feeds/unifeed.rss');
  $page->AddFeed('track7 site updates', '/feeds/updates.rss');
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->AddFeed('track7 page comments', '/feeds/comments.rss');
  $page->AddFeed('track7 bln entries', '/feeds/entries.rss');
  $page->AddFeed('track7 album photos', '/feeds/photos.rss');
  $page->AddFeed('track7 art', '/feeds/art.rss');
  $page->AddFeed('track7 disc golf rounds', '/feeds/rounds.rss');

  $page->Start('track7');
?>
      <div id="features">
        <h2>features</h2>
        <dl>
          <dt><a href="/analogu/"><img class="icon" src="/analogu/favicon.png" alt="" />the analog underground</a></dt>
          <dd>
            download free software with source code.
          </dd>
          <dt><a href="/output/pen/"><img class="icon" src="/output/pen/favicon.png" alt="" />pen vs. sword</a></dt>
          <dd>
            read short stories and theories.
          </dd>
          <dt><a href="/output/pen/bln/"><img class="icon" src="/output/pen/favicon.png" alt="" />bln (natural blog)</a></dt>
          <dd>
            find out what i think.
          </dd>
          <dt><a href="/output/lego/"><img class="icon" src="/output/lego/favicon.png" alt="" />lego models</a></dt>
          <dd>
            download instructions to build lego models.
          </dd>
          <dt><a href="/output/gfx/"><img class="icon" src="/output/favicon.png" alt="" />graphics</a></dt>
          <dd>
            see pencil sketches and digital artwork.
          </dd>
          <dt><a href="/output/gfx/album/"><img class="icon" src="/output/gfx/album/favicon.png" alt="" />photo album</a></dt>
          <dd>
            view my collection of photos.
          </dd>
          <dt><a href="/geek/guides/"><img class="icon" src="/geek/favicon.png" alt="" />guides</a></dt>
          <dd>
            learn something.
          </dd>
          <dt><a href="/geek/discgolf/"><img class="icon" src="/geek/favicon.png" alt="" />disc golf</a></dt>
          <dd>
            track disc golf scores.
          </dd>
          <dt><a href="/hb/"><img class="icon" src="/hb/favicon.png" alt="" />forums</a></dt>
          <dd>
            speak your mind and see what others think.
          </dd>
        </dl>
      </div>

<?
  // get last MAXITEMS items from updates, posts, comments, entries, and photos
  $updates = 'select instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, MAXITEMS, 'error looking up updates', ''))
    $update = $updates->NextRecord();
  else
    $update = false;
  $posts = 'select p.id, p.number, p.thread, p.instant, p.subject, p.post, p.uid, u.login from hbposts as p left join users as u on u.uid=p.uid order by instant desc';
  if($posts = $db->GetLimit($posts, 0, MAXITEMS, 'error looking up forum posts', ''))
    $post = $posts->NextRecord();
  else
    $post = false;
  $comments = 'select c.instant, c.page, c.uid, u.login, c.name, c.url, c.comments from comments as c left join users as u on u.uid=c.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, MAXITEMS, 'error looking up comments', ''))
    $comment = $comments->NextRecord();
  else
    $comment = false;
  $entries = 'select instant, name, title, post from bln where status=\'published\' order by instant desc';
  if($entries = $db->GetLimit($entries, 0, MAXITEMS, 'error looking up bln entries', ''))
    $entry = $entries->NextRecord();
  else
    $entry = false;
  $photos = 'select added, id, caption, description from photos order by added desc';
  if($photos = $db->GetLimit($photos, 0, MAXITEMS, 'error looking up album photos', ''))
    $photo = $photos->NextRecord();
  else
    $photo = false;
  $guides = 'select g.id, g.dateadded, g.title, g.description, u.login from guides as g left join users as u on u.uid=g.author order by dateadded desc';
  if($guides = $db->GetLimit($guides, 0, MAXITEMS, 'error looking up guides and tips', ''))
    $guide = $guides->NextRecord();
  else
    $guide = false;
  $arts = 'select id, name, type, description, adddate from art order by adddate desc';
  if($arts = $db->GetLimit($arts, 0, MAXITEMS, 'error looking up art', ''))
    $art = $arts->NextRecord();
  else
    $art = false;
  $rounds = 'select r.id, r.instant, r.courseid, c.name, r.player, r.uid, u.login, r.roundtype, r.tees, r.score, r.comments from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid where entryuid is null or r.uid=0 order by instant desc';
  if($rounds = $db->GetLimit($rounds, 0, MAXITEMS, 'error looking up rounds', ''))
    $round = $rounds->NextRecord();
  else
    $round = false;

  $items = 0;
  while($items < MAXITEMS && ($update || $post || $comment || $entry || $photo || $guide || $art || $round)) {
    if($update && (!$post || $update->instant >= $post->instant) && (!$comment || $update->instant >= $comment->instant) && (!$entry || $update->instant >= $entry->instant) && (!$photo || $update->instant >= $photo->added) && (!$guide || $update->instant >= $guide->dateadded) && (!$art || $update->instant >= $art->adddate) && (!$round || $update->instant >= $round->instant)) {
      showUpdate($update, $user);
      $update = $updates->NextRecord();
    } elseif($post && (!$update || $post->instant >= $update->instant) && (!$comment || $post->instant >= $comment->instant) && (!$entry || $post->instant >= $entry->instant) && (!$photo || $post->instant >= $photo->added) && (!$guide || $post->instant >= $guide->dateadded) && (!$art || $post->instant >= $art->adddate) && (!$round || $post->instant >= $round->instant)) {
      showPost($post, $user);
      $post = $posts->NextRecord();
    } elseif($comment && (!$update || $comment->instant >= $update->instant) && (!$post || $comment->instant >= $post->instant) && (!$entry || $comment->instant >= $entry->instant) && (!$photo || $comment->instant >= $photo->added) && (!$guide || $comment->instant >= $guide->dateadded) && (!$art || $comment->instant >= $art->adddate) && (!$round || $comment->instant >= $round->instant)) {
      showComment($comment, $user);
      $comment = $comments->NextRecord();
    } elseif($entry && (!$update || $entry->instant >= $update->instant) && (!$post || $entry->instant >= $post->instant) && (!$comment || $entry->instant >= $comment->instant) && (!$photo || $entry->instant >= $photo->added) && (!$guide || $entry->instant >= $guide->dateadded) && (!$art || $entry->instant >= $art->adddate) && (!$round || $entry->instant >= $round->instant)) {
      showEntry($entry, $user);
      $entry = $entries->NextRecord();
    } elseif($photo && (!$update || $photo->added >= $update->instant) && (!$post || $photo->added >= $post->instant) && (!$comment || $photo->added >= $comment->instant) && (!$entry || $photo->added >= $entry->instant) && (!$guide || $photo->added >= $guide->dateadded) && (!$art || $photo->added >= $art->adddate) && (!$round || $photo->added >= $round->instant)) {
      showPhoto($photo, $user);
      $photo = $photos->NextRecord();
    } elseif($guide && (!$update || $guide->dateadded >= $update->instant) && (!$post || $guide->dateadded >= $post->instant) && (!$comment || $guide->dateadded >= $comment->instant) && (!$entry || $guide->dateadded >= $entry->instant) && (!$photo || $guide->dateadded >= $photo->added) && (!$art || $guide->dateadded >= $art->adddate) && (!$round || $guide->dateadded >= $round->instant)) {
      showGuide($guide, $user);
      $guide = $guides->NextRecord();
    } elseif($art && (!$update || $art->adddate >= $update->instant) && (!$post || $art->adddate >= $post->instant) && (!$comment || $art->adddate >= $comment->instant) && (!$entry || $art->adddate >= $entry->instant) && (!$photo || $art->adddate >= $photo->added) && (!$guide || $art->adddate >= $guide->dateadded) && (!$round || $art->adddate >= $round->instant)) {
      showArt($art, $user);
      $art = $arts->NextRecord();
    } elseif($round) {
      showRound($round, $user);
      $round = $rounds->NextRecord();
    }
    $items++;
  }
?>

      <p class="links">[
        <a href="new.php">updates</a> |
        <a href="hb/recentposts.php">posts</a> |
        <a href="comments.php">comments</a>
      ]</p>
      <br class="clear" />
<?
  $page->End();

  /**
   * Show a site update.
   *
   * @param object $update site update to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showUpdate($update, $user) {
?>
    <div class="feed update">
      <div class="typedate" title="site update at <?=strtolower($user->tzdate(LONGDATEFMT, $update->instant)); ?>"><div class="date"><?=strtolower(auText::SmartTime($update->instant, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/updates.rss" class="feed" title="track7 updates"></a>track7 update by <a href="/user/misterhaan/">misterhaan</a></h2>
      <p><?=$update->change; ?></p>
    </div>

<?
  }

  /**
   * Show a forum post.
   *
   * @param object $post forum post to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showPost($post, $user) {
?>
    <div class="feed post">
      <div class="typedate" title="forum post at <?=strtolower($user->tzdate(LONGDATEFMT, $post->instant)); ?>"><div class="date"><?=strtolower(auText::SmartTime($post->instant, $user)); ?></div></div>
<?
    echo '      <h2 class="feed"><a href="/feeds/posts.rss" class="feed" title="track7 forum posts" /><a href="/hb/thread' . $post->thread;
    if($post->number - 1 > _FORUM_POSTS_PER_PAGE)
      echo '/skip=' . (floor(($post->number - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) . '#p';
    else
      echo '/#p';
    echo $post->id . '">' . $post->subject . '</a> by ';
    if($post->uid)
      echo '<a href="/user/' . $post->login . '/">' . $post->login . '</a>';
    else
      echo 'anonymous';
    echo "</h2>\n";
?>
      <?=$post->post; ?>
    </div>

<?
  }

  /**
   * Show a page comment.
   *
   * @param object $comment page comment to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showComment($comment, $user) {
    $pagename = explode('/', rtrim($comment->page, '/'));
    $pagename = $pagename[count($pagename) - 1];
?>
    <div class="feed comment">
      <div class="typedate" title="page comment at <?=strtolower($user->tzdate(LONGDATEFMT, $comment->instant)); ?>"><div class="date"><?=strtolower(auText::SmartTime($comment->instant, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/comments.rss" class="feed" title="track7 comments"></a>comment on <a href="<?=$comment->page; ?>"><?=$pagename; ?></a> by <?=($comment->uid ? '<a href="/user/' . $comment->login . '/">' . $comment->login . '</a>' : ($comment->url ? '<a href="' . $comment->url . '">' . $comment->name . '</a>' : $comment->name)); ?></h2>
      <?=$comment->comments; ?>
    </div>

<?
  }

  /**
   * Show a bln entry.
   *
   * @param object $entry bln entry to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showEntry($entry, $user) {
?>
    <div class="feed entry">
      <div class="typedate" title="bln entry at <?=strtolower($user->tzdate(LONGDATEFMT, $entry->instant)); ?>"><div class="date"><?=strtolower(auText::SmartTime($entry->instant, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/entries.rss" class="feed" title="track7 bln entries" /><a href="/output/pen/bln/<?=$entry->name; ?>"><?=$entry->title; ?></a> by <a href="/user/misterhaan/">misterhaan</a></h2>
      <p><?=$entry->post; ?></p>
    </div>

<?
  }

 /**
   * Show a photo.
   *
   * @param object $photo photo to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showPhoto($photo, $user) {
?>
    <div class="feed photo">
      <div class="typedate" title="photo at <?=strtolower($user->tzdate(LONGDATEFMT, $photo->added)); ?>"><div class="date"><?=strtolower(auText::SmartTime($photo->added, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/photos.rss" class="feed" title="track7 album photos" /><a href="/output/gfx/album/photo=<?=$photo->id; ?>"><?=$photo->caption; ?></a> by <a href="/user/misterhaan/">misterhaan</a></h2>
      <p><a class="img" href="/output/gfx/album/photo=<?=$photo->id; ?>"><img class="photothumb" src="/output/gfx/album/photos/<?=$photo->id; ?>.jpg" alt="" /></a></p>
      <p><?=$photo->description; ?></p>
    </div>

<?
  }

  /**
   * Show a guide.
   *
   * @param object $guide guide to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showGuide($guide, $user) {
?>
    <div class="feed guide">
      <div class="typedate" title="guide at <?=strtolower($user->tzdate(LONGDATEFMT, $guide->dateadded)); ?>"><div class="date"><?=strtolower(auText::SmartTime($guide->dateadded, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/guides.rss" class="feed" title="track7 guides" /><a href="/geek/guides/<?=$guide->id; ?>/"><?=$guide->title; ?></a> by <a href="/user/<?=$guide->login; ?>/"><?=$guide->login; ?></a></h2>
      <p><?=$guide->description; ?></p>
    </div>

<?
  }

  /**
   * Show art (a sketch or digital art).
   * @param object $art art to be shown
   * @param auUserTrack7 $user user object for showing dates in the correct time zone
   */
  function showArt($art, $user) {
    if($art->type == 'digital')
      $art->type = 'digital art';
    if(!$art->name)
      $art->name = str_replace('-', ' ', $art->id);
?>
    <div class="feed art">
      <div class="typedate" title="<?=$art->type; ?> at <?=strtolower($user->tzdate(LONGDATEFMT, $art->adddate)); ?>"><div class="date"><?=strtolower(auText::SmartTime($art->adddate, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/art.rss" class="feed" title="track7 art" /><a href="/output/gfx/sketch.php#<?=$art->id; ?>"><?=$art->name; ?></a> by <a href="/user/misterhaan/">misterhaan</a></h2>
      <p><a class="img" href="/output/gfx/sketch.php#<?=$art->id; ?>"><img class="photothumb" src="/output/gfx/<?=$art->id; ?>-prev.png" alt="" /></a></p>
      <?=$art->description; ?>
    </div>

<?
  }

  function showRound($round, $user) {
?>
    <div class="feed round">
      <div class="typedate" title="disc golf round at <?=strtolower($user->tzdate(LONGDATEFMT, $round->instant)); ?>"><div class="date"><?=strtolower(auText::SmartTime($round->instant, $user)); ?></div></div>
      <h2 class="feed"><a href="/feeds/rounds.rss" class="feed" title="track7 disc golf rounds" /><a href="/geek/discgolf/rounds.php?id=<?=$round->id; ?>">disc golf round</a> at <a href="/geek/discgolf/courses.php?id=<?=$round->courseid; ?>"><?=$round->name; ?></a> by <?=$round->uid ? '<a href="/user/' . $round->login . '/">' . $round->login . '</a>' : $round->player; ?></h2>
      <p>
        on <?=strtolower($user->tzdate('l, F j<\s\u\p>S</\s\u\p>, Y', $round->instant)); ?>,
<?
    if($round->uid) {
?>
        <a href="/geek/discgolf/players.php?p=<?=$round->login; ?>" title="more information on this player"><?=$round->login; ?></a>
<?
    } else
      echo $round->player;
?>
        played a <?=$round->roundtype; ?> round <?=$round->tees ? 'from the ' . $round->tees . ' tees ' : ''; ?>
        at <a href="/geek/discgolf/courses.php?id=<?=$round->courseid; ?>" title="more information on this course"><?=$round->name; ?></a>,
        scoring <?=$round->score; ?>.
      </p>
      <p><?=$round->comments; ?></p>
    </div>
<?
  }
?>
