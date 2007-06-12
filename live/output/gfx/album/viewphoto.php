<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!isset($_GET['id'])) {
    $page->Show404();
    die;
  }
  $photo = 'select id, caption, description, added, tags from photos where id=\'' . addslashes($_GET['id']) . '\'';
  if($photo = $db->GetRecord($photo, 'Error looking up photo', '')) {
    $page->Start($photo->caption . ' - photo album', $photo->caption);
    if($user->GodMode())
      $page->Info('<a href="editphoto.php?id=' . $photo->id . '">edit this photo</a>');
    require_once 'auText.php';
    $url = dirname($_SERVER['PHP_SELF']);
?>
      <img id="photo" src="<?=$url; ?>/photos/<?=$photo->id; ?>.jpeg" />
      <div id="photometa">
        <span id="added">added:&nbsp; <?=auText::HowLongAgo($photo->added); ?></span>
        <span id="tags">tags:&nbsp; <?=TagLinks($photo->tags, $url); ?></span>
      </div>
      <p><?=$photo->description; ?></p>
<?
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
?>
