<?
  //$getvars = array('book');
  if(isset($_GET['book'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
    $book = 'select id,header,footer from gbbooks where name=\'' . $_GET['book'] . '\'';
    $book = $db->Get($book);
    if($book->IsError())
      die('error reading database when trying to look up guestbook:<br>' . $book->GetMessage());
    if($book->NumRecords() < 1)
      die('could not find a guestbook named \'' . $_GET['book'] . '\' in the database -- nothing to do');
    $book = $book->NextRecord();
    echo $book->header;
    if(!isset($_GET[SKIP]) || !is_numeric($_GET[SKIP]))
      $_GET[SKIP] = 0;
    if(!isset($_GET[SHOW]) || !is_numeric($_GET[SHOW]))
      $_GET[SHOW] = 20;  $skip = $_GET['skip'];
    $entries = 'select entry from gbentries where bookid=' . $book->id . ' order by id desc'; // . ' limit 20 offset ' . (20 * $_GET['page']) . ' order by id desc';
    $entries = $db->GetSplit($entries, 20);
    if($entries->IsError())
      die('error reading database when trying to look up entries for this guestbook:<br>' . $entries->GetMessage());
    if($entries->NumRecords() < 1)
      echo '<p>there are no entries in this guestbook yet</p>';
    while($entry = $entries->NextRecord())
      echo $entry->entry;
    $page->SplitLinks();
    echo $book->footer;
  } else {
    echo 'no guestbook to view!';
  }
?>
