<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(!$user->IsAdmin()) {
    if(isset($_GET['ajax'])) {
      $ajax = new t7ajax();
      $ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
      $ajax->Send();
      die;
    }
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open('application not found - software');
?>
      <h1>404 application not found</h1>

      <p>
        sorry, we don’t seem to have an application by that name.  try the list of
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all applications</a>.
      </p>
<?php
    $html->Close();
    die;
  }

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'save':
        if($app = $db->query('select id, url from code_vs_applications where id=\'' . +$_POST['app'] . '\' limit 1'))
          if($app = $app->fetch_object())
            if(preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $_POST['version'])) {
              $ver = explode('.', $_POST['version']);
              if(!isset($_FILES['binfile']) || !$_FILES['binfile']['size'] || $_POST['binurl'] = SaveUpload('bin', $app->url))
                if(!isset($_FILES['bin32file']) || !$_FILES['bin32file']['size'] || $_POST['bin32url'] = SaveUpload('bin32', $app->url))
                  if(!isset($_FILES['srcfile']) || !$_FILES['srcfile']['size'] || $_POST['srcurl'] = SaveUpload('src', $app->url)) {
                    $released = $_POST['released'] ? t7format::LocalStrtotime($_POST['released']) : time();
                    $ins = 'insert into code_vs_releases set application=\'' . +$app->id . '\', released=\'' . +$released . '\', major=\'' . +$ver[0] . '\', minor=\'' . +$ver[1] . '\', revision=\'' . +$ver[2] . '\', lang=\'' . +$_POST['language'];
                    if($_POST['dotnet'])
                      $ins .= '\', dotnet=\'' . +$_POST['dotnet'];
                    $ins .= '\', studio=\'' . +$_POST['studio'] . '\', binurl=\'' . $db->escape_string($_POST['binurl']);
                    if(isset($_POST['bin32url']) && $_POST['bin32url'])
                      $ins .= '\', bin32url=\'' . $db->escape_string($_POST['bin32url']);
                    $ins .= '\', srcurl=\'' . $db->escape_string($_POST['srcurl']) . '\'';
                    $ajax->Data->query = $ins;
                    if($db->real_query($ins)) {
                      $ajax->Data->url = $app->url;
                      if(time() - $released < 604800)  // within the last week
                        t7send::Tweet($app->url . ' v' . $_POST['version'] . ' released', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $app->url);
                    } else
                      $ajax->Fail('error saving release to database.');
                  } else
                    $ajax->Fail('unable to save source code.');
                else
                  $ajax->Fail('unable to save 32-bit binary.');
              else
                $ajax->Fail('unable to save binary.');
            } else
              $ajax->Fail('version does not match required pattern.');
          else
            $ajax->Fail('application not found.');
        else
          $ajax->Fail('error looking up application.');
        break;
    }
    $ajax->Send();
    die;
  }

  if(isset($_GET['app']))
    if($app = $db->query('select id, name, url from code_vs_applications where id=\'' . +$_GET['app'] . '\' limit 1'))
      if($app = $app->fetch_object()) {
        $html = new t7html([]);
        $html->Open('add release - ' . htmlspecialchars($app->name));
?>
      <h1>add release:  <?php echo htmlspecialchars($app->name); ?></h1>
      <form id=addrel method=post enctype="multipart/form-data" data-appid=<?php echo $app->id; ?>>
        <input type=hidden name=app value=<?php echo $app->id; ?>>
        <label>
          <span class=label>version:</span>
          <span class=field><input id=version name=version maxlength=10 pattern="[0-9]+\.[0-9]+\.[0-9]+" required></span>
        </label>
        <label>
          <span class=label>date:</span>
          <span class=field><input id=released name=released></span>
        </label>
        <label>
          <span class=label>language:</span>
          <span class=field><select id=language name=language>
<?php
        if($langs = $db->query('select id, name from code_vs_lang order by name'))
          while($lang = $langs->fetch_object()) {
?>
            <option value=<?php echo $lang->id; ?>><?php echo $lang->name; ?></option>
<?php
          }
?>
          </select></span>
        </label>
        <label>
          <span class=label>.net:</span>
          <span class=field><select id=dotnet name=dotnet>
<?php
        if($dotnets = $db->query('select id, version from code_vs_dotnet order by id desc'))
          while($dotnet = $dotnets->fetch_object()) {
?>
            <option value="<?php echo $dotnet->id; ?>"><?php echo $dotnet->version; ?></option>
<?php
          }
?>
            <option value="">n/a</option>
          </select></span>
        </label>
        <label>
          <span class=label>studio:</span>
          <span class=field><select id=studio name=studio>
<?php
        if($studios = $db->query('select version, name from code_vs_studio order by version desc'))
          while($studio = $studios->fetch_object()) {
?>
            <option value=<?php echo $studio->version; ?>><?php echo $studio->name; ?></option>
<?php
          }
?>
          </select></span>
        </label>
        <label>
          <span class=label>bin url:</span>
          <span class=field><input id=binurl name=binurl maxlength=128></span>
        </label>
        <label>
          <span class=label>binary:</span>
          <span class=field><input type=file id=binfile name=binfile></span>
        </label>
        <label>
          <span class=label>bin32 url:</span>
          <span class=field><input id=bin32url name=bin32url maxlength=128></span>
        </label>
        <label>
          <span class=label>binary32:</span>
          <span class=field><input type=file id=bin32file name=bin32file></span>
        </label>
        <label>
          <span class=label>src url:</span>
          <span class=field><input id=srcurl name=srcurl maxlength=128></span>
        </label>
        <label>
          <span class=label>source:</span>
          <span class=field><input type=file id=srcfile name=srcfile></span>
        </label>
        <button id=save>save</button>
      </form>
<?php
        $html->Close();
      } else
        ;  // app not found
    else
      ;  // db error
  else
    ;  // app not provided

  function SaveUpload($type, $base) {
    $filename = $base . '-v' . $_POST['version'];
    switch($type) {
      case 'bin':
        if(isset($_FILES['bin32file']) && $_FILES['bin32file']['size'])
          $filename .= '_x64';
        break;
      case 'bin32':
        $filename .= '_x86';
        break;
      case 'src':
        $filename .= '_source';
    }
    $filename .= '.' . strtolower(GetExtension($_FILES[$type . 'file']['name']));
    if(move_uploaded_file($_FILES[$type . 'file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $filename))
      return $filename;
    return false;
  }

  function GetExtension($filename) {
    $parts = explode('.', $filename);
    return $parts[count($parts) - 1];
  }
?>
