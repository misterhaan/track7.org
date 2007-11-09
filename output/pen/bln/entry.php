<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['name'])) {
    $entry = 'select instant, tags, title, post from bln where name=\'' . addslashes($_GET['name']) . '\'';
    if($entry = $db->GetRecord($entry, 'error looking up entry', 'entry not found')) {
      if($entry->instant)
        $page->Start($entry->title . ' - bln', $entry->title, 'posted in ' . TagLinks($entry->tags) . ', ' . strtolower($user->tzdate('M j, Y', $entry->instant)));
      else
        $page->Start($entry->title . ' - bln', $entry->title, 'posted in ' . TagLinks($entry->tags));
      // DO:  allow admin to edit
?>
      <p>
        <?=$entry->post; ?>

      </p>
<?
      $page->SetFlag(_FLAG_PAGES_COMMENTS);  // show comments
      $page->End();
      die;
    }
  } elseif($user->GodMode && isset($_GET['edit'])) {
    // DO:  show add new entry form
  }
  $page->Show404();

  function TagLinks($tags) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }
?>