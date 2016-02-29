<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  if(!$user->IsAdmin()) {
    header('HTTP/1.0 404 Not Found');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    die;
  }

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'pull':
        chdir($_SERVER['DOCUMENT_ROOT']);
        exec('git pull', $output, $retcode);
        $ajax->Data->output = implode("\n", $output);
        $ajax->Data->retcode = $retcode;
        // TODO:  parse $output for files that cloudflare caches (for example, js and css), and use https://api.cloudflare.com/#zone-purge-individual-files-by-url-and-cache-tags to purge them
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: pull.');
        break;
    }
    $ajax->Send();
    die;
  }

  $html = new t7html([]);
  $html->Open('update from github');
?>
    <h1>update track7 from github</h1>

    <nav class=actions><a class=get href="#pull">update</a></nav>
<?php
  $html->Close();
?>
