<?
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['tags']) && $_GET['tags']) {
    $rss = new t7feed('track7 blog entries', '/bln/', 'blog entries posted at track7 tagged with ' . $_GET['tags'], 'copyright 2008 - 2016 track7');
    $tags = explode(',', $db->escape_string($_GET['tags']));
    $entries = 'select e.url, e.posted, e.title, e.content from blog_entrytags as et right join blog_tags as t on t.id=et.tag and t.name in (\'' . implode('\', \'', $tags) . '\') left join blog_entries as e on e.id=et.entry where e.status=\'published\' order by e.posted desc limit ' . t7feed::MAX_RESULTS;
  } else {
    $rss = new t7feed('all track7 blog entries', '/bln/', 'all blog entries posted at track7', 'copyright 2008 - 2016 track7');
    $entries = 'select url, posted, title, content from blog_entries where status=\'published\' order by posted desc limit ' . t7feed::MAX_RESULTS;
  }
  if($entries = $db->query($entries))
    while($entry = $entries->fetch_object()) {
      $p = strpos($entry->content, '</p>');
      $entry->content = substr($entry->content, 0, $p + 4);
      $entry->content = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $entry->content);
      $entry->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('‘', '’', '“', '”', '—'), $entry->title);
      $rss->AddItem($entry->content . '<p>» <a href="/bln/' . $entry->url . '">read more...</a></p>', $entry->title, '/bln/' . $entry->url, $entry->posted, '/bln/' . $entry->url, true);
    }
  $rss->End();
?>
