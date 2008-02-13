<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';

  if($_GET['tags'])
    if(substr($_GET['tags'], 0, 1) == '-') {
      $rss = new auFeed('guides and tips', '/geek/guides/', 'guides posted on track7 not tagged with ' . substr($_GET['tags'], 1), 'copyright 2008 track7');
      $tags = explode(',', substr($_GET['tags'], 1));
      foreach($tags as $tag)
        $guides .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $guides = 'select id, dateadded, title, description from guides where status=\'approved\' and not (' . $guides . ') order by dateadded desc';
    } else {
      $rss = new auFeed('guides and tips', '/geek/guides/', 'guides posted on track7 tagged with ' . substr($_GET['tags']), 'copyright 2008 track7');
      $tags = explode(',', $_GET['tags']);
      foreach($tags as $tag)
        $guides .= ' tags=\'' . $tag . '\' or tags like \'%,' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . ',%\'';
      $guides = 'select id, dateadded, title, description from guides where status=\'approved\' and (' . $guides . ') order by dateadded desc';
    }
  else {
    $rss = new auFeed('guides and tips', '/geek/guides/', 'all guides posted at track7', 'copyright 2008 track7');
    $guides = 'select id, dateadded, title, description from guides order by dateadded desc';
  }
  if($guides = $db->GetLimit($guides, 0, 15, '', ''))
    while($guide = $guides->NextRecord()) {
      $guide->title = str_replace(array('&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&mdash;'), array('\'', '\'', '"', '"', '--'), $guide->title);
      $rss->AddItem('<p>' . $guide->description . '</p>', $guide->title, '/geek/guides/' . $guide->id . '/', $guide->dateadded, '/geek/guides/' . $guide->id . '/', true);
    }
  $rss->End();
?>
