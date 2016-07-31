<?
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['tags']) && $_GET['tags']) {
    $rss = new t7feed('track7 photos', '/album/', 'photos posted at track7 tagged with ' . $_GET['tags'], 'copyright 2008 - 2016 track7');
    $tags = explode(',', $db->escape_string($_GET['tags']));
    $photos = 'select p.url, p.posted, p.caption, p.story from photos_taglinks as tl right join photos_tags as t on t.id=tl.tag and t.name in (\'' . implode('\', \'', $tags) . '\') left join photos as p on p.id=tl.photo order by p.posted desc limit ' . t7feed::MAX_RESULTS;
  } else {
    $rss = new t7feed('all track7 photos', '/album/', 'all photos posted at track7', 'copyright 2008 - 2016 track7');
    $photos = 'select url, posted, caption, story from photos order by posted desc limit ' . t7feed::MAX_RESULTS;
  }
  //$rss->AddItem($photos, 'query');
  if($photos = $db->query($photos))
    while($photo = $photos->fetch_object())
      $rss->AddItem('<p><img class=photo src="/album/photos/' . $photo->url . '.jpeg"></p>' . $photo->story, $photo->caption, '/album/' . $photo->url, $photo->posted, '/album/' . $photo->url, true);
  $rss->End();
?>
