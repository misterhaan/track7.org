<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  $rss = new auFeed();
  $rss->Start('track7 page comments', '/comments.php', 'all comments posted on track7 pages', 'copyright 2007 track7');

  $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments from comments as c left join users as u on c.uid=u.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, 15, '', ''))
    while($comment = $comments->NextRecord()) {
      $commentpage = explode('/', $comment->page);
      $commentpage = $commentpage[count($commentpage) - 1];
      $rss->AddItem($comment->comments, '[comment] ' . $commentpage . ' - ' . ($comment->uid ? $comment->login : $comment->name), '/comments.php#c' . $comment->id, $comment->instant, '/comments.php#c' . $comment->id, true);
    }
  $rss->End();
?>
