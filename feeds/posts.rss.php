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
  $posts = 'select p.id, p.number, p.tid, t.fid, p.subject, p.post, f.title as forum, p.uid, u.login, p.instant from oiposts as p left join oithreads as t on p.tid=t.id left join oiforums as f on t.fid=f.id left join users as u on p.uid=u.uid order by p.instant desc';
  if($posts = $db->GetLimit($posts, 0, 15, '', ''))
    while($post = $posts->NextRecord()) {
      $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
      if(strlen($post->subject) > 27)
        $post->subject = substr($post->subject, 0, 25) . '...';
?>
    <item>
      <title>[<?=$post->forum; ?>] <?=$post->subject; ?> - <?=$post->uid ? $post->login : 'anonymous'; ?></title>
      <link>http://<?=$_SERVER['HTTP_HOST']; ?>/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?></link>
      <description><![CDATA[<?=$post->post; ?>]]></description>
      <pubDate><?=gmdate('r', $post->instant); ?></pubDate>
      <guid>http://<?=$_SERVER['HTTP_HOST']; ?>/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?></guid>
    </item>

<?
    }
?>
  </channel>
</rss>
