<?
  $getvars = array('author');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $posts = 'select p.instant, p.uid, u.login, p.subject, p.thread, t.title, t.tags from hbposts as p left join users as u on u.uid=p.uid left join hbthreads as t on t.id=p.thread order by p.instant desc';
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->Start('recent forum posts', 'recent forum posts<a class="feed" href="/feeds/posts.rss" title="rss feed of recent forum posts"><img src="/style/feed.png" alt="feed" /></a>');
  if($posts = $db->GetSplit($posts, 20, 0, '', '', 'error looking up recent posts', 'no posts found')) {
    require_once 'auText.php';
    require_once 'hb.inc';
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>date</th><th>author</th><th>post</th><th>thread</th><th>tags</th></tr></thead>
        <tbody>
<?
    while($post = $posts->NextRecord()) {
      if(strlen($post->subject) > 27)
        $post->subject = substr($post->subject, 0, 25) . '...';
      if(strlen($post->title) > 17)
        $post->title = substr($post->title, 0, 15) . '...';
?>
          <tr><td><?=strtolower(auText::SmartTime($post->instant, $user)); ?></td><td><?=$post->uid ? '<a href="/user/' . $post->login . '"/>' . $post->login . '</a>' : 'anonymous'; ?></td><td><a href="thread<?=$post->thread; ?>/<?=$post->number - 1 > _FORUM_POSTS_PER_THREAD ? 'skip=' . (floor(($post->number - 1) / _FORUM_POSTS_PER_THREAD) * _FORUM_POSTS_PER_THREAD) : ''; ?>#<?=$post->id; ?>"><?=$post->subject; ?></a></td><td><a href="thread<?=$post->thread; ?>/"><?=$post->title; ?></a></td><td><?=HB::TagLinks($post->tags); ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
    $page->SplitLinks();
  }
  $page->End();
?>
