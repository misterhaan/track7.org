<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';

  $rss = new auFeed('track7 art', '/output/gfx/', 'art posted on track7', 'copyright 2010 track7');
  $arts = 'select id, name, type, description, adddate from art order by adddate desc';
  if($arts = $db->GetLimit($arts, 0, 15, '', ''))
    while($art = $arts->NextRecord()) {
      if(!$art->name)
        $art->name = str_replace('-', ' ', $art->id);
      $rss->AddItem('<p><img src="http://' . $_SERVER['HTTP_HOST'] . '/output/gfx/' . $art->id . '.png" alt="" /></p><p>' . $art->description . '</p>', $art->name, '/output/gfx/' . $art->type . '.php#' . $art->id, $art->adddate, '/output/gfx/' . $art->type . '.php#' . $art->id, true);
    }
  $rss->End();
?>
