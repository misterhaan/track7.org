<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($_GET['tags'])
    if(substr($_GET['tags'], 0, 1) == '-') {
      $rss = new auFeed('track7 photos', '/album/', 'photos posted on track7 not tagged with ' . substr($_GET['tags'], 1), 'copyright 2008 - 2011 track7');
      $tags = explode(',', substr($_GET['tags'], 1));
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, tags, youtubeid, caption, added, description from photos where not (' . $photos . ') order by added desc';
    } else {
      $rss = new auFeed('track7 photos', '/album/', 'photos posted on track7 tagged with ' . $_GET['tags'], 'copyright 2008 - 2011 track7');
      $tags = explode(',', $_GET['tags']);
      foreach($tags as $tag)
        $photos .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $photos = 'select id, tags, youtubeid, caption, added, description from photos where' . $photos . ' order by added desc';
    }
  else {
    $rss = new auFeed('all track7 photos', '/album/', 'all photos posted on track7', 'copyright 2008 - 2011 track7');
    $photos = 'select id, tags, youtubeid, caption, added, description from photos order by added desc';
  }
  if($photos = $db->GetLimit($photos, 0, 15, '', ''))
    while($photo = $photos->NextRecord()) {
      $photo->caption = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $photo->caption);
      $tags = '<p>tags:&nbsp; ' . str_replace(',', ', ', $photo->tags) . '</p>';
      if($photo->youtubeid)
        $rss->AddItem($tags . '<p><a href="http://www.youtube.com/watch?v=' . $photo->youtubeid . '">watch this video on youtube</a></p><p>' . $photo->description . '</p>', $photo->caption, '/album/photo=' . $photo->id, $photo->added, '/album/photo=' . $photo->id, true);
      else
        $rss->AddItem($tags . '<p><img src="http://' . $_SERVER['HTTP_HOST'] . '/album/photos/' . $photo->id . '.jpeg" alt="" /></p><p>' . $photo->description . '</p>', $photo->caption, '/album/photo=' . $photo->id, $photo->added, '/album/photo=' . $photo->id, true);
    }
  $rss->End();
?>
