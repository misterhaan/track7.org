<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  $rss = new auFeed('track7 forum posts', '/hb/', 'all posts from the track7 forums', 'copyright 2007 track7');

  $posts = 'select p.id, p.number, p.thread, p.subject, p.post, p.uid, u.login, p.instant from hbposts as p left join users as u on p.uid=u.uid order by p.instant desc';
  if($posts = $db->GetLimit($posts, 0, 15, '', ''))
    while($post = $posts->NextRecord()) {
      $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
      if(strlen($post->subject) > 27)
        $post->subject = substr($post->subject, 0, 25) . '...';
        $link = '/hb/thread' . $post->thread . ($post->number ? '/skip=' . $post->number : '/') . '#p' . $post->id;
        $rss->AddItem($post->post, $post->subject . ' - ' . ($post->uid ? $post->login : 'anonymous'), $link, $post->instant, $link, true);
    }
  $rss->End();
?>
