<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';

  if($_GET['tags'])
    if(substr($_GET['tags'], 0, 1) == '-') {
      $rss = new auFeed('bln entries', '/output/pen/bln/', 'entries posted on bln at track7 not tagged with ' . substr($_GET['tags'], 1), 'copyright 2008 track7');
      $tags = explode(',', substr($_GET['tags'], 1));
      foreach($tags as $tag)
        $entries .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $entries = 'select name, instant, tags, title, post from bln where not (' . $entries . ') order by instant desc';
    } else {
      $rss = new auFeed('bln entries', '/output/pen/bln/', 'entries posted on bln at track7 tagged with ' . $_GET['tags'], 'copyright 2008 track7');
      $tags = explode(',', $_GET['tags']);
      foreach($tags as $tag)
        $entries .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $entries = 'select name, instant, tags, title, post from bln where' . $entries . ' order by instant desc';
    }
  else {
    $rss = new auFeed('all bln entries', '/output/pen/bln/', 'all entries posted on bln at track7', 'copyright 2008 track7');
    $entries = 'select name, instant, tags, title, post from bln order by instant desc';
  }
  if($entries = $db->GetLimit($entries, 0, 15, '', ''))
    while($entry = $entries->NextRecord()) {
      $entry->post = str_replace('href="/', 'href="http://' . $_SERVER['HTTP_HOST'] . '/', $entry->post);
      $entry->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $entry->title);
      $rss->AddItem('<p>' . $entry->post . '</p>', $entry->title, '/output/pen/bln/' . $entry->name, $entry->instant, '/output/pen/bln/' . $entry->name, true);
    }
  $rss->End();
?>
