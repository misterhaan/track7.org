<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['tags']) && $_GET['tags']) {
    $rss = new t7feed('track7 art', '/art/', 'art posted at track7 tagged with ' . $_GET['tags'], 'copyright 2008 - 2016 track7');
    $tags = explode(',', $db->escape_string($_GET['tags']));
    $arts = 'select a.url, i.ext, a.posted, a.title, p.deschtml from art_taglinks as tl right join art_tags as t on t.id=tl.tag and t.name in (\'' . implode('\', \'', $tags) . '\') left join art as a on a.id=tl.art left join image_formats as i on i.id=a.format order by a.posted desc limit ' . t7feed::MAX_RESULTS;
  } else {
    $rss = new t7feed('all track7 art', '/art/', 'all art posted at track7', 'copyright 2008 - 2016 track7');
    $arts = 'select a.url, i.ext, a.posted, a.title, a.deschtml from art as a left join image_formats as i on i.id=a.format order by a.posted desc limit ' . t7feed::MAX_RESULTS;
  }
  if($arts = $db->query($arts))
    while($art = $arts->fetch_object())
      $rss->AddItem('<p><img class=art src="/art/img/' . $art->url . '.' . $art->ext . '"></p>' . $art->deschtml, $art->title, '/art/' . $art->url, $art->posted, '/art/' . $art->url, true);
  $rss->End();
?>
