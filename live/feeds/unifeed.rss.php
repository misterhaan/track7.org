<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  define('MAXITEMS', 15);
  
  $rss = new auFeed('track7', '/', 'track7 site updates, forum posts, and page comments unifeed', 'copyright 2007 track7');
  
  $updates = 'select id, instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, MAXITEMS, '', ''))
    $update = $updates->NextRecord();
  else
    $update = false;

  $posts = 'select p.id, p.number, p.tid, t.fid, p.subject, p.post, f.title as forum, p.uid, u.login, p.instant from oiposts as p left join oithreads as t on p.tid=t.id left join oiforums as f on t.fid=f.id left join users as u on p.uid=u.uid order by p.instant desc';
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

  $items = 0;
  while($items < MAXITEMS && ($update || $post || $comment || $entry)) {
    if($update && (!$post || $update->instant >= $post->instant) && (!$comment || $update->instant >= $comment->instant) && (!$entry || $update->instant >= $entry->instant)) {
      AddUpdate($rss, $update);
      $update = $updates->NextRecord();
    } elseif($post && (!$update || $post->instant >= $update->instant) && (!$comment || $post->instant >= $comment->instant) && (!$entry || $post->instant >= $entry->instant)) {
      AddPost($rss, $post);
      $post = $posts->NextRecord();
    } elseif($comment && (!$update || $comment->instant >= $update->instant) && (!$post || $comment->instant >= $post->instant) && (!$entry || $comment->instant >= $entry->instant)) {
      AddComment($rss, $comment);
      $comment = $comments->NextRecord();
    } elseif($entry && (!$update || $entry->instant >= $update->instant) && (!$post || $entry->instant >= $post->instant) && (!$comment || $entry->instant >= $comment->instant)) {
      AddEntry($rss, $entry);
      $entry = $entries->NextRecord();
    }
    $items++;
  }
  $rss->End();

  function AddUpdate($rss, $update) {
    $update->change = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $update->change);
    $rss->AddItem($update->change, '', '', $update->instant, 'update' . $update->id);
  }

  function AddPost($rss, $post) {
    $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
    if(strlen($post->subject) > 27)
      $post->subject = substr($post->subject, 0, 25) . '...';
    $link = '/oi/f' . $post->fid . '/t' . $post->tid . '/' . ($post->number ? '&amp;skip=' . $post->number : '') . '#p' .$post->id;
    $rss->AddItem($post->post, '[post in ' . $post->forum . '] ' . $post->subject . ' - ' . ($post->uid ? $post->login : 'anonymous'), $link, $post->instant, $link, true);
  }

  function AddComment($rss, $comment) {
    $commentpage = explode('/', $comment->page);
    $commentpage = $commentpage[count($commentpage) - 1];
    $rss->AddItem($comment->comments, '[comment] ' . $commentpage . ' - ' . ($comment->uid ? $comment->login : $comment->name), '/comments.php#c' . $comment->id, $comment->instant, '/comments.php#c' . $comment->id, true);
  }

  function AddEntry($rss, $entry) {
    $entry->post = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $entry->post);
    $entry->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $entry->title);
    $rss->AddItem('<p>' . $entry->post . '</p>', $entry->title, 'http://' . $_SERVER['HTTP_HOST'] . '/output/pen/bln/' . $entry->name, $entry->instant, 'http://' . $_SERVER['HTTP_HOST'] . '/output/pen/bln/' . $entry->name, true);
  }
?>