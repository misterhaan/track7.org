<?
  if(isset($_GET['file']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['file'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
    $page->LogHit('/' . $_GET['file']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' . $_GET['file']);
    die;
  }
?>
