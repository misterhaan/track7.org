<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('the many deaths of mr. linzmeyer');
  switch($_GET['comic']) {
    case 3:
      echo '      <img src="linz/lapd.png" alt="" class="comic" style="width: 500px; height: 194px;" />';
      break;
    case 2:
      echo '      <img src="linz/highlander.png" alt="" class="comic" style="width: 500px; height: 389px;" />';
      break;
    default:
      $_GET['comic'] = 1;
      echo '      <img src="linz/desperado.png" alt="" class="comic" style="width: 500px; height: 417px;" />';
      break;
  }
?>


      <div class="pagelinks">
        comic:&nbsp;
<?
  for($comic = 1; $comic <= 3; $comic++)
    if($comic == $_GET['comic'])
      echo '        <span class="active">' . $comic . '</span>' . "\n";
    else
      echo '        <a href="' . $_SERVER['PHP_SELF'] . '?comic=' . $comic . '">' . $comic . '</a>' . "\n";
?>
      </div>
<?
  $page->End();
?>
