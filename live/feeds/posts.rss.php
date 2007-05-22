<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  $rss = new auFeed();
  $rss->Start('track7 forum posts', '/oi/', 'all posts from the track7 forums', 'copyright 2007 track7');

  $posts = 'select p.id, p.number, p.tid, t.fid, p.subject, p.post, f.title as forum, p.uid, u.login, p.instant from oiposts as p left join oithreads as t on p.tid=t.id left join oiforums as f on t.fid=f.id left join users as u on p.uid=u.uid order by p.instant desc';
  if($posts = $db->GetLimit($posts, 0, 15, '', ''))
    while($post = $posts->NextRecord()) {
      $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
      if(strlen($post->subject) > 27)
        $post->subject = substr($post->subject, 0, 25) . '...';
        $link = '/oi/f' . $post->fid . '/t' . $post->tid . '/' . ($post->number ? '&amp;skip=' . $post->number : '') . '#p' .$post->id;
        $rss->AddItem($post->post, '[post in ' . $post->forum . '] ' . $post->subject . ' - ' . ($post->uid ? $post->login : 'anonymous'), $link, $post->instant, $link, true);
    }
  $rss->End();
?>
