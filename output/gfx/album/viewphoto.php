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
    ShowTagPrevNext($db, $photo->tags, $photo->added, $url);
    $page->SetFlag(_FLAG_PAGES_COMMENTS);
    $page->End();
    die;
  }
  $page->Show404();

  function TagLinks($tags, $url) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="' . $url . '/tag/' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }

  function ShowTagPrevNext(&$db, $tags, $added, $url) {
    $tags = explode(',', $tags);
    if(count($tags)) {
      echo "\n" . '      <table id="tagnav" class="columns" cellspacing="0">' . "\n";
      foreach($tags as $tag) {
        echo '        <tr><th>' . $tag . '</th><td>';
        $prev = 'select id from photos where added<' . $added . ' and (tags=\'' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . '\' or tags like \'%,' . $tag . ',%\') order by added desc';
        if(false !== $prev = $db->GetValue($prev, 'error looking up previous photo for ' . $tag, ''))
          echo '<a title="view the previous photo tagged with ' . $tag . '" href="' . $url . '/photo/' . $prev . '">previous</a>';
        else
          echo 'previous';
        echo '</td><td><a title="view all photos tagged with ' . $tag . '" href="' . $url . '/tag/' . $tag . '">all</a></td><td>';
        $next = 'select id from photos where added>' . $added . ' and (tags=\'' . $tag . '\' or tags like \'' . $tag . ',%\' or tags like \'%,' . $tag . '\' or tags like \'%,' . $tag . ',%\') order by added';
        if(false !== $next = $db->GetValue($next, 'error looking up next photo tagged ' . $tag, ''))
          echo '<a title="view the next photo tagged with ' . $tag . '" href="' . $url . '/photo/' . $next . '">next</a>';
        else
          echo 'next';
        echo "</td></tr>\n";
      }
      echo "      </table>\n";
    }
  }
?>
