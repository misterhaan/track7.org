<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  header('Content-Type: application/rss+xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title>track7 updates</title>
    <link>http://<?=$_SERVER['HTTP_HOST']; ?>/new.php</link>
    <description>track7 site updates feed</description>
    <language>en-us</language>
    <copyright>copyright 2007 track7</copyright>
    <generator>PHP/<?=phpversion(); ?></generator>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>

<?
  $updates = 'select id, instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, 15, '', ''))
    while($update = $updates->NextRecord()) {
      $update->change = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $update->change); 
?>
    <item>
      <description><![CDATA[<?=$update->change; ?>]]></description>
      <pubDate><?=gmdate('r', $update->instant); ?></pubDate>
      <guid isPermaLink="false">update<?=$update->id; ?></guid>
    </item>

<?
    }
?>
  </channel>
</rss>
