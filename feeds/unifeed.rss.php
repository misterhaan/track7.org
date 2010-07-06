<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  define('MAXITEMS', 15);

  $rss = new auFeed('track7', '/', 'track7 site updates, forum posts, page comments, bln entries, album photos, and art unifeed', 'copyright 2008 - 2010 track7');

  $updates = 'select id, instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, MAXITEMS, '', ''))
    $update = $updates->NextRecord();
  else
    $update = false;

  $posts = 'select p.id, p.number, p.thread, p.subject, p.post, p.uid, u.login, p.instant from hbposts as p left join users as u on p.uid=u.uid order by p.instant desc';
  if($posts = $db->GetLimit($posts, 0, MAXITEMS, '', ''))
    $post = $posts->NextRecord();
  else
    $post = false;

  $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments from comments as c left join users as u on c.uid=u.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, MAXITEMS, '', ''))
    $comment = $comments->NextRecord();
  else
    $comment = false;

  if($_GET['bln'])
    if(substr($_GET['bln'], 0, 1) == '-') {
      $tags = explode(',', substr($_GET['tags'], 1));
      foreach($tags as $tag)
        $entries .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $entries = 'select name, instant, tags, title, post from bln where not (' . $entries . ') order by instant desc';
    } else {
      $tags = explode(',', $_GET['bln']);
      foreach($tags as $tag)
        $entries .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $entries = 'select name, instant, tags, title, post from bln where' . $entries . ' order by instant desc';
    }
  else
    $entries = 'select name, instant, tags, title, post from bln order by instant desc';
  if($entries = $db->GetLimit($entries, 0, MAXITEMS, '', ''))
    $entry = $entries->NextRecord();
   else
     $entry = false;

  if($_GET['album'])
    if(substr($_GET['album'], 0, 1) == '-') {
      $tags = explode(',', substr($_GET['album'], 1));
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, caption, added, description from photos where not (' . $photos . ') order by added desc';
    } else {
      $tags = explode(',', $_GET['album']);
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, caption, added, description from photos where' . $photos . ' order by added desc';
    }
  else
    $photos = 'select id, youtubeid, caption, added, description from photos order by added desc';
  if($photos = $db->GetLimit($photos, 0, MAXITEMS, '', ''))
    $photo = $photos->NextRecord();
  else
    $photo = false;

  if($_GET['guides'])
    if(substr($_GET['guides'], 0, 1) == '-') {
      $tags = explode(',', substr($_GET['guides'], 1));
      foreach($tags as $tag)
        $guides .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $guides = 'select id, dateadded, title, description from guides where status=\'approved\' and not (' . $guides . ') order by dateadded desc';
    } else {
      $tags = explode(',', $_GET['guides']);
      foreach($tags as $tag)
        $guides .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $guides = 'select id, dateadded, title, description from guides where status=\'approved\' and (' . $guides . ') order by dateadded desc';
    }
  else
    $guides = 'select id, dateadded, title, description from guides order by dateadded desc';
  if($guides = $db->GetLimit($guides, 0, MAXITEMS, '', ''))
    $guide = $guides->NextRecord();
  else
    $guide = false;

  $arts = 'select id, name, type, description, adddate from art order by adddate desc';
  if($arts = $db->GetLimit($arts, 0, 15, '', ''))
    $art = $arts->NextRecord();
  else
    $art = false;

  $items = 0;
  while($items < MAXITEMS && ($update || $post || $comment || $entry || $photo || $guide || $art)) {
    if($update && (!$post || $update->instant >= $post->instant) && (!$comment || $update->instant >= $comment->instant) && (!$entry || $update->instant >= $entry->instant) && (!$photo || $update->instant >= $photo->added) && (!$guide || $update->instant >= $guide->dateadded) && (!$art || $update->instant >= $art->adddate)) {
      AddUpdate($rss, $update);
      $update = $updates->NextRecord();
    } elseif($post && (!$update || $post->instant >= $update->instant) && (!$comment || $post->instant >= $comment->instant) && (!$entry || $post->instant >= $entry->instant) && (!$photo || $post->instant >= $photo->added) && (!$guide || $post->instant >= $guide->dateadded) && (!$art || $post->instant >= $art->adddate)) {
      AddPost($rss, $post);
      $post = $posts->NextRecord();
    } elseif($comment && (!$update || $comment->instant >= $update->instant) && (!$post || $comment->instant >= $post->instant) && (!$entry || $comment->instant >= $entry->instant) && (!$photo || $comment->instant >= $photo->added) && (!$guide || $comment->instant >= $guide->dateadded) && (!$art || $comment->instant >= $art->adddate)) {
      AddComment($rss, $comment);
      $comment = $comments->NextRecord();
    } elseif($entry && (!$update || $entry->instant >= $update->instant) && (!$post || $entry->instant >= $post->instant) && (!$comment || $entry->instant >= $comment->instant) && (!$photo || $entry->instant >= $photo->added) && (!$guide || $entry->instant >= $guide->dateadded) && (!$art || $entry->instant >= $art->adddate)) {
      AddEntry($rss, $entry);
      $entry = $entries->NextRecord();
    } elseif($photo && (!$update || $photo->added >= $update->instant) && (!$post || $photo->added >= $post->instant) && (!$comment || $photo->added >= $comment->instant) && (!$entry || $photo->added >= $entry->instant) && (!$guide || $photo->added >= $guide->dateadded) && (!$art || $photo->added >= $art->adddate)) {
      AddPhoto($rss, $photo);
      $photo = $photos->NextRecord();
    } elseif($guide && (!$update || $guide->dateadded >= $update->instant) && (!$post || $guide->dateadded >= $post->instant) && (!$comment || $guide->dateadded >= $comment->instant) && (!$entry || $guide->dateadded >= $entry->instant) && (!$photo || $guide->dateadded >= $photo->added) && (!$art || $guide->dateadded >= $art->adddate)) {
      AddGuide($rss, $guide);
      $guide = $guides->NextRecord();
    } elseif($art && (!$update || $art->adddate >= $update->instant) && (!$post || $art->adddate >= $post->instant) && (!$comment || $art->adddate >= $comment->instant) && (!$entry || $art->adddate >= $entry->instant) && (!$photo || $art->adddate >= $photo->added) && (!$guide || $art->adddate >= $guide->dateadded)) {
      AddArt($rss, $art);
      $art = $arts->NextRecord();
    }
    $items++;
  }
  $rss->End();

  function AddUpdate($rss, $update) {
    $update->change = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $update->change);
    $rss->AddItem($update->change, '[update]', '', $update->instant, 'update' . $update->id);
  }

  function AddPost($rss, $post) {
    $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
    $post->subject = html_entity_decode($post->subject, ENT_COMPAT, _CHARSET);
    if(strlen($post->subject) > 27)
      $post->subject = substr($post->subject, 0, 25) . '...';
    $link = '/hb/thread' . $post->thread . ($post->number ? '/skip=' . $post->number : '/') . '#p' . $post->id;
    $rss->AddItem($post->post, '[post] ' . $post->subject . ' - ' . ($post->uid ? $post->login : 'anonymous'), $link, $post->instant, $link, true);
  }

  function AddComment($rss, $comment) {
    $commentpage = explode('/', rtrim($comment->page, '/'));
    $commentpage = $commentpage[count($commentpage) - 1];
    $rss->AddItem($comment->comments, '[comment] ' . $commentpage . ' - ' . ($comment->uid ? $comment->login : $comment->name), '/comments.php#c' . $comment->id, $comment->instant, '/comments.php#c' . $comment->id, true);
  }

  function AddEntry($rss, $entry) {
    $entry->post = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $entry->post);
    $entry->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $entry->title);
    $rss->AddItem('<p>' . $entry->post . '</p>', '[bln] ' . $entry->title, 'http://' . $_SERVER['HTTP_HOST'] . '/output/pen/bln/' . $entry->name, $entry->instant, '/output/pen/bln/' . $entry->name, true);
  }

  function AddPhoto($rss, $photo) {
    $photo->caption = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $photo->caption);
    if($photo->youtubeid)
      $rss->AddItem('<p><a href="http://www.youtube.com/watch?v=' . $photo->youtubeid . '">watch this video on youtube</a></p><p>' . $photo->description . '</p>', '[photo] ' . $photo->caption, '/output/gfx/album/photo/' . $photo->id, $photo->added, '/output/gfx/album/photo/' . $photo->id, true);
    else
      $rss->AddItem('<p><img src="http://' . $_SERVER['HTTP_HOST'] . '/output/gfx/album/photos/' . $photo->id . '.jpeg" alt="" /></p><p>' . $photo->description . '</p>', '[photo] ' . $photo->caption, '/output/gfx/album/photo/' . $photo->id, $photo->added, '/output/gfx/album/photo/' . $photo->id, true);
  }

  function AddGuide($rss, $guide) {
    $guide->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $guide->title);
    $rss->AddItem('<p>' . $guide->description . '</p>', '[guide] ' . $guide->title, '/geek/guides/' . $guide->id . '/', $guide->dateadded, '/geek/guides/' . $guide->id . '/', true);
  }

  function AddArt($rss, $art) {
    if(!$art->name)
      $art->name = str_replace('-', ' ', $art->id);
    $rss->AddItem('<p><img src="http://' . $_SERVER['HTTP_HOST'] . '/output/gfx/' . $art->id . '.png" alt="" /></p><p>' . $art->description . '</p>', '[art] ' . $art->name, '/output/gfx/' . $art->type . '.php#' . $art->id, $art->adddate, '/output/gfx/' . $art->type . '.php#' . $art->id, true);
  }
?>