<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $url = dirname($_SERVER['PHP_SELF']) . '/';
  $phurl = $url;
  if($user->GodMode) {
    $page->Info('<a href="' . dirname($_SERVER['PHP_SELF']) . '/editphoto.php">add a new photo</a>');
    $page->Info('<a href="' . dirname($_SERVER['PHP_SELF']) . '/editvideo.php">add a new video</a>');
  }
  if(isset($_GET['tag'])) {
    $tag = htmlentities($_GET['tag'], ENT_COMPAT, _CHARSET);
    $phurl .= 'tag=' . $tag . '/';
    $page->Start($tag . ' - photo album', 'photo album [' . $tag . ']<a class="feed" href="/feeds/photos.rss?tags=' . $tag . '" title="rss feed of album photos tagged with ' . $tag . '"><img src="/style/feed.png" alt="feed" /></a>');
    $photos = addslashes($_GET['tag']);
    $photos = ' where tags=\'' . $photos . '\' or tags like \'' . $photos . ',%\' or tags like \'%,' . $photos . '\' or tags like \'%,' . $photos . ',%\'';
    $page->heading($tag);
    $page->ShowTagDescription('photos', $tag);
    if($user->GodMode)
      $page->Info('<a href="/tools/taginfo.php?type=photos&amp;name=' . $tag . '">add/edit tag description</a>');
  } else {
    $tag = false;
    $page->Start('photo album', 'photo album<a class="feed" href="/feeds/photos.rss" title="rss feed of album photos"><img src="/style/feed.png" alt="feed" /></a>');
    $page->TagCloud('photos', $url . 'tag=', 4, 7, 17, 40, 3);
  }
  switch($_GET['sort']) {
    case 'oldest':
      $sortadj = 'oldest';
      $sortsql = 'added';
      $phurl .= 'sort=oldest/';
      break;
    default:
      $sortadj = 'newest';
      $sortsql = 'added desc';
      break;
  }
  $photos = 'select id, caption, added from photos' . $photos . ' order by ' . $sortsql;
  if($photos = $db->GetSplit($photos, 24, 0, '', '', 'error looking up photos', isset($_GET['tag']) ? 'no photos tagged with ' . $tag : 'no photos found')) {
?>
      <p>showing <?=$db->split_count; ?> photos (<?=$_GET['show']; ?> at a time)</p>
      <?=getSortLinks($db, $url, $tag); ?>


      <ul id="photos">
        <li>
<?
    while($photo = $photos->NextRecord()) {
      if($started) {
?>
        </li><li>
<?
      }
?>
          <a href="<?=$phurl; ?>photo=<?=$photo->id; ?>">
            <span class="photopreview">
              <img src="<?=$url; ?>photos/<?=$photo->id; ?>.jpg" alt="" <?=auFile::ImageSizeCSS(_ROOT . '/output/gfx/album/photos/' . $photo->id . '.jpg'); ?>/>
            </span>
            <span class="caption"><?=$photo->caption; ?></span>
          </a>
<?
      $started = true;
    }
?>
        </li>
      </ul>
<?
    $url = false;
    $q = '?';
    if($_GET['tag']) {
      $url .= $_GET['tag'];
      $q = '&';
    }
    if($_GET['sort']) {
      $url .= $q . 'sort=' . $_GET['sort'];
      $q = '&';
    }
    $page->SplitLinks(htmlspecialchars($q), $url);
  }
  $page->End();

  /**
   * gets HTML with linked options for how to sort thumbnails.
   */
  function getSortLinks(&$db, $url, $tag) {
    if($tag)
      $url .= 'tag=' . $tag;
    $ret = '<div id="sortoptions">sort by:&nbsp; ';
    if(!$_GET['sort'])
      $ret .= 'newest';
    else
      $ret .= '<a href="' . $url . '" title="show newest photos first">newest</a>';
    if($_GET['sort'] == 'oldest')
      $ret .= ' | oldest';
    else {
      if(substr($url, -1) != '/')
        $url .= '/';
      $ret .= ' | <a href="' . $url . 'sort=oldest" title="show oldest photos first">oldest</a>';
    }
    return $ret . '</div>';
  }
?>
