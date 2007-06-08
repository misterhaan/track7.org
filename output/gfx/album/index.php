<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['tag'])) {
    $tag = htmlentities($_GET['tag']);
    $page->Start($tag . ' - photo album', 'photo album [' . $tag . ']');
    $photos = addslashes($_GET['tag']); 
    $photos = ' where tags=\'' . $photos . '\' or tags like \'' . $photos . ',%\' or tags like \'%,' . $photos . '\' or tags like \'%,' . $photos . ',%\'';
    $page->Info('viewing photos tagged with ' . htmlentities($_GET['tag']) . '.&nbsp; <a href="../album/">go back</a> to choose a different tag.');
    $page->ShowTagDescription('photos', $tag);
  } else {
    $page->Start('photo album');
    $page->TagCloud('photos');
  }
  $photos = 'select id, caption, added from photos' . $photos . ' order by added desc';
  if($photos = $db->GetSplit($photos, 20, 0, '', '', 'error looking up photos', isset($_GET['tag']) ? 'no photos tagged with ' . htmlentities($_GET['tag']) : 'no photos found')) {
?>
      <div id="photos">
<?
    while($photo = $photos->NextRecord()) {
      
    }
?>
      </div>
<?
    $page->SplitLinks();
  }
  $page->End();
?>
