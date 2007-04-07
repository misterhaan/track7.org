<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('hosted guestbooks', 'list of hosted guestbooks');
  $books = 'select name from gbbooks';
  if($books = $db->Get($books, 'error reading list of guestbooks from database', 'no hosted guestbooks found')) {
?>
      <ul>
<?
    while($b = $books->NextRecord()) {
?>
        <li><a href="view.php?book=<?=$b->name; ?>"><?=$b->name; ?></a></li>
<?
    }
?>
      </ul>

<?
  }
  $page->End();
?>