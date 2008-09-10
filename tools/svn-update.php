<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode)
    $page->Show404();
  $page->Start('track7 update from svn');
  $path = $_SERVER['DOCUMENT_ROOT'];
  if($_GET['path'])
    $path .= '/' . $_GET['path'];
  if(isset($_GET['update'])) {
    $page->Heading('updating track.org/' . htmlspecialchars($_GET['path']));
    exec('svn update ' . $path, $output);
?>
      <samp><?=auText::EOL2br(implode("\n", $output)); ?></samp>
<?
  }
  $page->Heading('select directory / file to update');
  echo '      <ul class="path">' . "\n";
  echo '        <li class="up"><a href="svn-update.php">track7.org</a><a class="update" href="?update" title="update this directory and all its contents"><img alt="update" src="/style/svn-update.png" /></a><ul>';
  $indent = '          ';
  $relpath = $_GET['path'];
  if(!is_dir($path))
    $relpath = dirname($relpath);
  if($relpath) {
    $dirs = explode('/', $relpath);
    $relpath = '';
    foreach($dirs as $d) {
      if($relpath)
        $relpath .= '/';
      $relpath .= $d;
      echo $indent . '<li class="up"><a href="?path=' . $relpath . '">' . $d . '</a><a class="update" href="?path=' . $relpath . '&amp;update" title="update this directory and all its contents"><img alt="update" src="/style/svn-update.png" /></a><ul>' . "\n";
      $indent .= '  ';
    }
  }
  if($dir = opendir($path)) {
    while($f = readdir($dir))
      if(substr($f,0,1) != '.' || $f == '.htaccess') {
        echo $indent . '<li class="';
        if($isdir = is_dir($path . '/' . $f))
          echo 'dir"><a href="?path=' . ($relpath ? $relpath . '/' : '') . $f . '">' . $f . '</a>';
        else
          echo 'file">' . $f;
        echo '<a class="update" href="?path=' . ($relpath ? $relpath . '/' : '') . $f . '&amp;update" title="update this ' . ($isdir ? 'directory and all its contents' : 'file') . '"><img alt="update" src="/style/svn-update.png" /></a></li>' . "\n";
      }
    closedir($dir);
  } else
    $page->Error('unable to read directory');
  for($indent = substr($indent, 2); strlen($indent) > 6; $indent = substr($indent, 2))
    echo $indent . "</ul></li>\n";
  echo "      </ul>\n";
  $page->End();
?>
