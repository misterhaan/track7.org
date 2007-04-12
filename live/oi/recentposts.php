<?
  $getvars = array('author');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';

  $posts = 'select p.id, p.number, p.tid, t.fid, p.subject, t.title as thread, f.title as forum, p.uid, u.login, p.instant from oiposts as p left join oithreads as t on p.tid=t.id left join oiforums as f on t.fid=f.id left join users as u on p.uid=u.uid';
  if(isset($_GET['author'])) {
    $u = 'select uid, login from users where login=\'' . $_GET['author'] . '\'';
    if($u = $db->GetRecord($u, 'error looking up user', 'user not found')) {
      $posts .= ' and u.uid=' . $u->uid;
      $page->Start($u->login . '\'s posts - oi', $u->login . '\'s posts');
    }
  }
  $posts .= ' order by p.instant desc';
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->Start('most recent posts - oi', 'most recent posts<a class="feed" href="/feeds/posts.rss" title="rss feed of most recent posts"><img src="/style/feed.png" alt="feed" /></a>');
  if($posts = $db->GetSplit($posts, 20, 0, '', '', 'error looking up recent posts', 'no posts found')) {
?>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>date</th><th>author</th><th>post</th><th>thread</th><th>forum</th></tr></thead>
        <tbody>
<?
    while($post = $posts->NextRecord()) {
      $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
      if(strlen($post->subject) > 27)
        $post->subject = substr($post->subject, 0, 25) . '...';
      if(strlen($post->thread) > 17)
        $post->thread = substr($post->thread, 0, 15) . '...';
?>
          <tr><td><?=auText::SmartDate($post->instant, $user); ?></td><td><?=($post->uid ? '<a href="/user/' . $post->login . '/">' . $post->login . '</a>' : 'anonymous'); ?></td><td><a href="f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?>"><?=$post->subject; ?></a></td><td><a href="f<?=$post->fid; ?>/t<?=$post->tid; ?>/"><?=$post->thread; ?></a></td><td><a href="f<?=$post->fid; ?>/"><?=$post->forum; ?></a></td></tr>
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
