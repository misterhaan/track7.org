<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!isset($_GET['id'])) {
    $page->Show404();
    die;
  }
  $photo = 'select id, youtubeid, caption, description, taken, added, tags from photos where id=\'' . addslashes($_GET['id']) . '\'';
  if($photo = $db->GetRecord($photo, 'Error looking up photo', '')) {
    $page->Start($photo->caption . ' - photo album', $photo->caption);
    $url = dirname($_SERVER['PHP_SELF']);
    if($user->GodMode)
      $page->Info('<a href="' . $url . '/edit' . ($photo->youtubeid ? 'video' : 'photo') . '.php?id=' . $photo->id . '">edit this ' . ($photo->youtubeid ? 'video' : 'photo') . '</a>');

    $tagnav = GetTagPrevNext($db, $photo->tags, $photo->added, $url);
    echo $tagnav;
    if($photo->youtubeid) {
      if($page->Mobile) {
?>
        <p class="photo"><a href="http://www.youtube.com/watch?v=<?=$photo->youtubeid; ?>">
          <img id="photo"  src="/output/gfx/album/photos/<?=$photo->id; ?>.jpg" />
          watch this video on youtube
        </a></p>
<?
      } else {
?>
      <object id="photo" width="640" height="385" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">
        <param name="movie" value="http://www.youtube.com/v/<?=$photo->youtubeid; ?>&amp;hl=en&amp;fs=1&amp;rel=0"></param>
        <param name="allowFullScreen" value="true"></param>
        <param name="allowscriptaccess" value="always"></param>
        <noscript><p class="info">enable javascript to see this <a href="http://www.youtube.com/watch?v=<?=$photo->youtubeid; ?>">youtube video</a> here</p></noscript>
      </object>
<?
        if(file_exists(_ROOT . $url . '/photos/' . $photo->id . '.avi')) {
?>
      <ul id="avidownload"><li><a href="<?=$url; ?>/photos/<?=$photo->id; ?>.avi">download this video</a> (avi with xvid / mp3)</li></ul>
<?
        }
      }
    } else {
?>
      <img id="photo" src="<?=$url; ?>/photos/<?=$photo->id; ?>.jpeg" alt="" />
<?
    }
?>
      <div id="photometa">
<?
    if($photo->taken)
      if($photo->taken > 2010) {
?>
        <span id="taken" title="<?=strtolower($user->tzdate('g:i a, D M j, Y', $photo->taken)); ?>">taken:&nbsp; <?=auText::HowLongAgo($photo->taken); ?> ago</span>
<?
      } else {
?>
        <span id="taken">taken:&nbsp; <?=$photo->taken; ?></span>
<?
      }
?>
        <span id="added" title="<?=strtolower($user->tzdate('g:i a, D M j, Y', $photo->added)); ?>">added:&nbsp; <?=auText::HowLongAgo($photo->added); ?> ago</span>
        <span id="tags">tags:&nbsp; <?=TagLinks($photo->tags, $url); ?></span>
      </div>
      <p><?=$photo->description; ?></p>
<?
    echo $tagnav;
    $page->ShowComments($url . '/photo/' . $photo->id);
    $page->End();
    die;
  }
  $page->Show404();

  function TagLinks($tags, $url) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="' . $url . '/tag=' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }

  function GetTagPrevNext(&$db, $tags, $added, $url) {
    $tags = explode(',', $tags);
    $ret = '      <div class="tagnav">' . "\n";
    if(in_array($_GET['tag'], $tags)) {
      $tag = addslashes($_GET['tag']);
      $older = 'select id from photos where added<' . $added . ' and (tags=\'' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . '\' or tags like \'%,' . $tag . ',%\') order by added desc';
      $older = $db->GetValue($older, 'error looking up older photo for tag ' . $_GET['tag']);
      $newer = 'select id from photos where added>' . $added . ' and (tags=\'' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . '\' or tags like \'%,' . $tag . ',%\') order by added';
      $newer = $db->GetValue($newer, 'error looking up newer photo for tag ' . $_GET['tag']);
      if(false !== $tag = array_search($_GET['tag'], $tags))
        unset($tags[$tag]);
    } else {
      $_GET['tag'] = '';
      $older = 'select id from photos where added<' . $added . ' order by added desc';
      $older = $db->GetValue($older, 'error looking up older photo');
      $newer = 'select id from photos where added>' . $added . ' order by added';
      $newer = $db->GetValue($newer, 'error looking up newer photo');
    }
    if($oldest = $_GET['sort'] == 'oldest') {
      $prev = $older;
      $prevname = 'older';
      $next = $newer;
      $nextname = 'newer';
    } else {
      $prev = $newer;
      $prevname = 'newer';
      $next = $older;
      $nextname = 'older';
    }
    if($prev) {
      $ret .= '        <a href="photo=' . $prev . '" title="see the previous photo';
      if($_GET['tag'])
        $ret .= ' tagged with ' . $_GET['tag'];
      $ret .= '">◄ ' . $prevname . "</a>\n";
    } else
      $ret .= '        <span>' . $prevname . "</span>\n";
    $url1 = dirname($_SERVER['PHP_SELF']) . '/tag=';
    $url2 = '/photo=' . htmlspecialchars($_GET['id'], ENT_COMPAT, _CHARSET);
    $ret .= '        <a ';
    if($_GET['tag'])
      $ret .= 'class="tag" ';
    $ret .= 'href="' . $url . '/';
    if($_GET['tag']) {
      $tag = htmlspecialchars($_GET['tag'], ENT_COMPAT, _CHARSET);
      $ret .= 'tag=' . $tag;
      if($oldest)
        $ret .= '/sort=oldest';
      $ret .= '" title="view everything tagged with ' . $tag . '">' . $tag;
    } else {
      if($oldest)
        $ret .= '/sort=oldest';
      $ret .= '" title="view everything">everything';
    }
    $ret .= "</a>\n";
    if($next) {
    $ret .= '        <a href="photo=' . $next . '" title="see the next photo';
    if($_GET['tag'])
      $ret .= ' tagged with ' . $_GET['tag'];
    $ret .= '">' . $nextname . " ►</a>\n";
    } else
      $ret .= '        <span>' . $nextname . "</span>\n";
    $ret .= '      </div>' . "\n";
    return $ret;
  }
?>
