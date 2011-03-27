<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(isset($_GET['id'])) {
    $story = 'select id, name, title, subtitle, pretitle, section from penstories where id=\'' . addslashes($_GET['id']) . '\'';
    if($story = $db->GetRecord($story, '', '')) {
      $url = dirname($_SERVER['PHP_SELF']) . '/' . $story->section . '/' . $story->id . '.php';
      if($_SERVER['REQUEST_URI'] != $url) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $url);
        die;
      }
      $page->Start($story->name . ' - ' . $story->section . ' - pen vs. sword - output', $story->title, $story->subtitle, $story->pretitle);
      include _ROOT . dirname($_SERVER['PHP_SELF']) . '/' . $story->id . '.html';
      $page->SetFlag(_FLAG_PAGES_COMMENTS);
      $page->End();
      die;
    }
  }
  $page->Show404();
?>