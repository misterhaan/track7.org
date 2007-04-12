<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  header('Content-Type: application/rss+xml; charset=utf-8');
  define('MAXITEMS', 15);
  echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title>track7</title>
    <link>http://<?=$_SERVER['HTTP_HOST']; ?>/</link>
    <description>track7 site updates, forum posts, and page comments unifeed</description>
    <language>en-us</language>
    <copyright>copyright 2007 track7</copyright>
    <generator>PHP/<?=phpversion(); ?></generator>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>

<?
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

  $items = 0;
  while($items < MAXITEMS && ($update || $post || $comment)) {
    if($update && (!$post || $update->instant >= $post->instant) && (!$comment || $update->instant >= $comment->instant)) {
      AddUpdate($update);
      $update = $updates->NextRecord();
    } elseif($post && (!$update || $post->instant >= $update->instant) && (!$comment || $post->instant >= $comment->instant)) {
      AddPost($post);
      $post = $posts->NextRecord();
    } elseif($comment && (!$update || $comment->instant >= $update->instant) && (!$post || $comment->instant >= $post->instant)) {
      AddComment($comment);
      $comment = $comments->NextRecord();
    }
    $items++;
  }
?>
  </channel>
</rss>
<?
  function AddUpdate($update) {
    $update->change = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $update->change); 
?>
    <item>
      <description><![CDATA[<?=$update->change; ?>]]></description>
      <pubDate><?=gmdate('r', $update->instant); ?></pubDate>
      <guid isPermaLink="false">update<?=$update->id; ?></guid>
    </item>

<?
  }

  function AddPost($post) {
    $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
    if(strlen($post->subject) > 27)
      $post->subject = substr($post->subject, 0, 25) . '...';
?>
    <item>
      <title>[post in <?=$post->forum; ?>] <?=$post->subject; ?> - <?=$post->uid ? $post->login : 'anonymous'; ?></title>
      <link>http://<?=$_SERVER['HTTP_HOST']; ?>/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?></link>
      <description><![CDATA[<?=$post->post; ?>]]></description>
      <pubDate><?=gmdate('r', $post->instant); ?></pubDate>
      <guid>http://<?=$_SERVER['HTTP_HOST']; ?>/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?></guid>
    </item>

<?
  }

  function AddComment($comment) {
    $commentpage = explode('/', $comment->page);
    $commentpage = $commentpage[count($commentpage) - 1];
?>
    <item>
      <title>[comment] <?=$commentpage; ?> - <?=$comment->uid ? $comment->login : $comment->name; ?></title>
      <link>http://<?=$_SERVER['HTTP_HOST']; ?>/comments.php#c<?=$comment->id; ?></link>
      <description><![CDATA[<?=$comment->comments; ?>]]></description>
      <pubDate><?=gmdate('r', $comment->instant); ?></pubDate>
      <guid>http://<?=$_SERVER['HTTP_HOST']; ?>/comments.php#c<?=$comment->id; ?></guid>
    </item>

<?
  }
?>