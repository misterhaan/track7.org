<?php
  define('MAXITEMS', 16);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $rss = new t7feed('track7 unifeed', '/', 'all track7 activity', 'copyright 2006 - 2016 track7');

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
      if($act->hasmore)
        $act->preview .= '<p><a href="' . htmlspecialchars($act->url) . '">⇨ read more</a></p>';
      $rss->AddItem($act->preview, ContributionPrefix($act->conttype) . $act->title . ' by ' . AuthorName($act), $act->url, $act->posted, $act->url, true);
      $act = $acts->fetch_object();
    } elseif($update && (!$forum || $update->posted > $forum->posted) && (!$comment || $update->posted > $comment->posted) && (!$photo || $update->posted > $photo->posted) && (!$guide || $update->posted > $guide->posted) && (!$art || $update->posted > $art->posted) && (!$round || $update->posted > $round->posted)) {
      $rss->AddItem('<p>' . $update->preview . '</p>', 'track7 update by misterhaan', 'http://' . $_SERVER['HTTP_HOST'] . '/', $update->posted, 'http://' . $_SERVER['HTTP_HOST'] . '/', true);
      $update = $updates->fetch_object();
    } elseif($forum && (!$comment || $forum->posted > $comment->posted) && (!$photo || $forum->posted > $photo->posted) && (!$guide || $forum->posted > $guide->posted) && (!$art || $forum->posted > $art->posted) && (!$round || $forum->posted > $round->posted)) {
      $forum->url = '/hb/thread' . $forum->thread . '/';
      if($forum->number - 1 > FORUM_POSTS_PER_PAGE)
        $forum->url .= 'skip=' . floor(($forum->number - 1) / FORUM_POSTS_PER_PAGE) * FORUM_POSTS_PER_PAGE;
      $forum->url .= '#p' . $forum->id;
      $rss->AddItem($forum->preview, $forum->title . ' by ' . AuthorName($forum), $forum->url, $forum->posted, $forum->url, true);
      $forum = $forums->fetch_object();
    } elseif($comment && (!$photo || $comment->posted > $photo->posted) && (!$guide || $comment->posted > $guide->posted) && (!$round || $comment->posted > $round->posted)) {
      $rss->AddItem($comment->preview, 'comment on ' . $comment->title . ' by ' . AuthorName($comment), $comment->url, $comment->posted, $comment->url, true);
      $comment = $comments->fetch_object();
    } elseif($photo && (!$guide || $photo->posted > $guide->posted) && (!$art || $photo->posted > $art->posted) && (!$round || $photo->posted > $round->posted)) {
      $rss->AddItem('<p><a href="/album/photo=' . $photo->id . '"><img class=photothumb src="/album/photos/' . $photo->id . '.jpg" alt=""></a></p><p class=readmore><a href="/album/photo=' . $photo->id . '">⇨  see larger</a></p>', $photo->title . ' by misterhaan', '/album/photo=' . $photo->id, $photo->posted, '/album/photo=' . $photo->id, true);
      $photo = $photos->fetch_object();
    } elseif($guide && (!$art || $guide->posted > $art->posted) && (!$round || $guide->posted > $round->posted)) {
      $rss->AddItem('<p>' . $guide->preview . '</p>', $guide->title . ' by ' . AuthorName($guide), '/guides/' . $guide->id . '/', $guide->posted, '/guides/' . $guide->id . '/', true);
      $guide = $guides->fetch_object();
    } elseif($art && (!$round || $art->posted > $round->posted)) {
      if(!$art->title)
        $art->title = str_replace('-', ' ', $art->id);
      $art->url = '/output/gfx/' . $art->type . '.php#' . $art->id;
      $rss->AddItem('<p><a href="' . $art->url . '"><img class=photothumb src="/output/gfx/' . $art->id. '-prev.png" alt=""></a></p><p class=readmore><a href="' . $art->url . '">⇨  see larger</a></p>', $art->title . ' by misterhaan', $art->url, $art->posted, $art->url, true);
      $art = $arts->fetch_object();
    } elseif($round) {
      $rss->AddItem('<p>scored ' . $round->score . ' in a ' . $round->roundtype . ' round' . ($round->tees ? ' from the ' . $round->tees . ' tees.' : '.') . '</p><p class=readmore><a href="/discgolf/rounds.php?id=' . $round->id . '">⇨ read more</a></p>', 'disc golf round at ' . $round->name . ' by ' . AuthorName($round), '/discgolf/rounds.php?id=' . $round->id, $round->posted, '/discgolf/rounds.php?id=' . $round->id, true);
      $round = $rounds->fetch_object();
    }
    $items++;
  }

  $rss->End();

  function ContributionPrefix($type) {
    switch($type) {
      case 'comment':
        return 'comment on ';
    }
    return '';
  }

  function AuthorName($act) {
    if($act->displayname)
      return $act->displayname;
    if($act->username)
      return $act->username;
    if($act->authorname)
      return $act->authorname;
    return 'anonymous';
  }
?>
