<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('the nazi teacher party');
  switch($_GET['comic']) {
    case 4:
      echo '      <img src="nazi/wanted.png" alt="" class="comic" style="width: 500px; height: 286px;" />';
      break;
    case 3:
      echo '      <img src="nazi/branding.png" alt="" class="comic" style="width: 500px; height: 360px;" />';
      break;
    case 2:
      echo '      <img src="nazi/court.png" alt="" class="comic" style="width: 500px; height: 243px;" />';
      break;
    default:
      $_GET['comic'] = 1;
      echo '      <img src="nazi/hatsoff.png" alt="" class="comic" style="width: 500px; height: 349px;" />';
      break;
  }
?>


      <div class="pagelinks">
        comic:&nbsp;
<?
  for($comic = 1; $comic <= 4; $comic++)
    if($comic == $_GET['comic'])
      echo '        <span class="active">' . $comic . '</span>' . "\n";
    else
      echo '        <a href="' . $_SERVER['PHP_SELF'] . '?comic=' . $comic . '">' . $comic . '</a>' . "\n";
?>
      </div>
<?
  $page->End();
?>
