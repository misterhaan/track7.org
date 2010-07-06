<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';
  $rss = new auFeed('track7 updates', '/new.php', 'track7 site updates feed', 'copyright 2008 - 2010 track7');

  $updates = 'select id, instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, 15, '', ''))
    while($update = $updates->NextRecord()) {
      $update->change = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $update->change);
      $rss->AddItem($update->change, '[update]', '', $update->instant, 'update' . $update->id);
    }
  $rss->End();
?>
