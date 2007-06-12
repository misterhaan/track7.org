<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $url = dirname($_SERVER['PHP_SELF']) . '/';
  if($user->GodMode)
    $page->Info('<a href="editphoto.php">add a new photo</a>');
  if(isset($_GET['tag'])) {
    $tag = htmlentities($_GET['tag']);
    $page->Start($tag . ' - photo album', 'photo album [' . $tag . ']');
    $photos = addslashes($_GET['tag']); 
    $photos = ' where tags=\'' . $photos . '\' or tags like \'' . $photos . ',%\' or tags like \'%,' . $photos . '\' or tags like \'%,' . $photos . ',%\'';
    $page->Info('viewing photos tagged with ' . htmlentities($_GET['tag']) . '.&nbsp; <a href="../album/">go back</a> to choose a different tag.');
    $page->ShowTagDescription('photos', $tag);
  } else {
    $page->Start('photo album');
    $page->TagCloud('photos', $url . 'tag/');
  }
  $photos = 'select id, caption, added from photos' . $photos . ' order by added desc';
  if($photos = $db->GetSplit($photos, 20, 0, '', '', 'error looking up photos', isset($_GET['tag']) ? 'no photos tagged with ' . htmlentities($_GET['tag']) : 'no photos found')) {
?>
      <div id="photos">
<?
    while($photo = $photos->NextRecord()) {
?>
        <a class="photoframe" href="<?=$url; ?>photo/<?=$photo->id; ?>">
          <span class="photopreview">
            <span class="iefix"></span>
            <img src="<?=$url; ?>photos/<?=$photo->id; ?>.jpg" alt="" />
          </span>
          <span class="caption"><?=$photo->caption; ?></span>
        </a>
<?
    }
?>
      </div>
      <br class="clear" />
<?
    $page->SplitLinks();
  }
  $page->End();
?>
