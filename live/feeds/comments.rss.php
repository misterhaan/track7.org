<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  header('Content-Type: application/rss+xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title>track7 forum posts</title>
    <link>http://<?=$_SERVER['HTTP_HOST']; ?>/oi/</link>
    <description>all posts from the track7 forums</description>
    <language>en-us</language>
    <copyright>copyright 2007 track7</copyright>
    <generator>PHP/<?=phpversion(); ?></generator>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>

<?
  $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments from comments as c left join users as u on c.uid=u.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, 15, '', ''))
    while($comment = $comments->NextRecord()) {
      $commentpage = explode('/', $comment->page);
      $commentpage = $commentpage[count($commentpage) - 1];
?>
    <item>
      <title><?=$commentpage; ?> - <?=$comment->uid ? $comment->login : $comment->name; ?></title>
      <link>http://<?=$_SERVER['HTTP_HOST']; ?>/comments.php#c<?=$comment->id; ?></link>
      <description><![CDATA[<?=$comment->comments; ?>]]></description>
      <pubDate><?=gmdate('r', $comment->instant); ?></pubDate>
      <guid>http://<?=$_SERVER['HTTP_HOST']; ?>/comments.php#c<?=$comment->id; ?></guid>
    </item>

<?
    }
?>
  </channel>
</rss>
