<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  $rss = new t7feed('track7 stories', '/pen/', 'stories posted at track7', 'copyright 2008 - 2017 track7');
  $stories = 'select posted, url, title, deschtml from stories order by posted desc limit ' . t7feed::MAX_RESULTS;
  if($stories = $db->query($stories))
    while($story = $stories->fetch_object())
      $rss->AddItem($story->deschtml, $story->title, '/pen/' . $story->url, $story->posted, '/pen/' . $story->url, true);
  $rss->End();
?>
