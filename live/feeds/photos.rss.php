<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';

  if($_GET['tags'])
    if(substr($_GET['tags'], 0, 1) == '-') {
      $rss = new auFeed('track7 photos', '/output/gfx/album/', 'photos posted on track7 not tagged with ' . substr($_GET['tags'], 1), 'copyright 2008 track7');
      $tags = explode(',', substr($_GET['tags'], 1));
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, caption, added, description from photos where not (' . $photos . ') order by added desc';
    } else {
      $rss = new auFeed('track7 photos', '/output/gfx/album/', 'photos posted on track7 tagged with ' . $_GET['tags'], 'copyright 2008 track7');
      $tags = explode(',', $_GET['tags']);
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, caption, added, description from photos where' . $photos . ' order by added desc';
    }
  else {
    $rss = new auFeed('all track7 photos', '/output/gfx/album/', 'all photos posted on track7', 'copyright 2008 track7');
    $photos = 'select id, youtubeid, caption, added, description from photos order by added desc';
  }
  if($photos = $db->GetLimit($photos, 0, 15, '', ''))
    while($photo = $photos->NextRecord()) {
      $photo->caption = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $photo->caption);
      if($photo->youtubeid)
        $rss->AddItem('<p><a href="http://www.youtube.com/watch?v=' . $photo->youtubeid . '">watch this video on youtube</a></p><p>' . $photo->description . '</p>', $photo->caption, '/output/gfx/album/photo/' . $photo->id, $photo->added, '/output/gfx/album/photo/' . $photo->id, true);
      else
        $rss->AddItem('<p><img src="http://' . $_SERVER['HTTP_HOST'] . '/output/gfx/album/photos/' . $photo->id . '.jpeg" alt="" /></p><p>' . $photo->description . '</p>', $photo->caption, '/output/gfx/album/photo/' . $photo->id, $photo->added, '/output/gfx/album/photo/' . $photo->id, true);
    }
  $rss->End();
?>
