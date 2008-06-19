<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode)
    $page->Show404();
  $page->Start('auLib update from svn');
?>
      <samp><?=htmlspecialchars(exec('svn update ' . dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/')); ?></samp>
<?
  $page->End();
?>
