<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode)
    $page->Show404();
  $page->Start('track7 update from svn');
?>
      <samp><?=htmlspecialchars(exec('svn update ' . $_SERVER['DOCUMENT_ROOT'])); ?></samp>
<?
  $page->End();
?>
