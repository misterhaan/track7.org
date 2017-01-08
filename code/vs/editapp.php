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
      case 'get':
        if(isset($_GET['id']) && $_GET['id'])
          if($app = $db->query('select url, name, descmd, github, wiki from code_vs_applications where id=\'' . +$_GET['id'] . '\' limit 1'))
            if($app = $app->fetch_object())
              $ajax->Data = $app;
            else
              $ajax->Fail('application not found.');
          else
            $ajax->Fail('database error looking up application for editing.');
        else
          $ajax->Fail('get requires an id.');
        break;
      case 'save':
        $ajax->Data->fieldIssues = [];
        $_POST['name'] = trim($_POST['name']);
        if(!$_POST['name']) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'name', 'issue' => 'name is required'];
        }
        $_POST['url'] = trim($_POST['url']);
        if(!$_POST['url'])
          $_POST['url'] = preg_replace('/[^a-z0-9\.\-_]*/', '', str_replace(' ', '-', strtolower($_POST['name'])));
        if(!preg_match('/^[a-z0-9\.\-_]{1,32}$/', $_POST['url'])) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be 1 - 32 lowercase letters, numbers, dashes, and periods.'];
        }
        if($check = $db->query('select id, name from code_vs_applications where url=\'' . $db->escape_string($_POST['url']) . '\' limit 1'))
          if($check = $check->fetch_object())
            if(!isset($_POST['id']) || $check->id != $_POST['id']) {
              $ajax->Data->fail = true;
              $ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be unique!  already in use by application named ' . $check->name];
            }
        $_POST['desc'] = trim($_POST['desc']);
        if(!$_POST['desc']) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'desc', 'issue' => 'description is required.'];
        }
        if($ajax->Data->fail)
          $ajax->Data->message = 'at least one field is invalid.';
        else {
          unset($ajax->Data->fieldIssues);
          if(isset($_FILES['icon']) && $_FILES['icon']['size'])
            move_uploaded_file($_FILES['icon']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $_POST['url'] . '.png');
          $ajax->Data->url = $_POST['url'];
          $id = false;
          if(isset($_POST['id']))
            if($db->query('update code_vs_applications set name=\'' . $db->escape_string($_POST['name']) . '\', url=\'' . $db->escape_string($_POST['url']) . '\', descmd=\'' . $db->escape_string($_POST['desc']) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown($_POST['desc'])) . '\', github=\'' . $db->escape_string($_POST['github']) . '\', wiki=\'' . $db->escape_string($_POST['wiki']) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
              $id = +$_POST['id'];
            else
              $ajax->Fail('failed to update application due to database error.');
          else
            if($db->query('insert into code_vs_applications (name, url, descmd, deschtml, github, wiki) values (\'' . $db->escape_string($_POST['name']) . '\', \'' . $db->escape_string($_POST['url']) . '\', \'' . $db->escape_string($_POST['desc']) . '\', \'' . $db->escape_string(t7format::Markdown($_POST['desc'])) . '\', \'' . $db->escape_string($_POST['github']) . '\', \'' . $db->escape_string($_POST['wiki']) . '\')'))
              $id = $db->insert_id;
            else
              $ajax->Fail('failed to add application due to database error.');
        }
        break;
    }
    $ajax->Send();
    die;
  }

  $id = isset($_GET['id']) ? +$_GET['id'] : false;
  $html = new t7html([]);
  $html->Open(($id ? 'edit' : 'add') . ' application - software');
?>
      <h1><?php echo $id ? 'edit' : 'add'; ?> application</h1>
      <form id=editapp method=post enctype="">
<?php
  if($id) {
?>
        <input type=hidden id=appid name=id value="<?php echo $id; ?>">
<?php
  }
?>
        <label>
          <span class=label>name:</span>
          <span class=field><input id=name name=name maxlength=32 required data-bind="value: name"></span>
        </label>
        <label>
          <span class=label>url:</span>
          <span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-\._]+" data-bind="value: url"></span>
        </label>
        <label class=multiline>
          <span class=label>description:</span>
          <span class=field><textarea id=desc name=desc required rows="" cols="" data-bind="value: desc"></textarea></span>
        </label>
        <label>
          <span class=label>icon:</span>
          <span class=field><input type=file name=icon></span>
        </label>
        <label>
          <span class=label>github:</span>
          <span class=field>https://github.com/misterhaan/<input id=github name=github maxlength=16></span>
        </label>
        <label>
          <span class=label>auwiki:</span>
          <span class=field>http://wiki.track7.org/<input id=wiki name=wiki maxlength=32></span>
        </label>
        <button id=save>save</button>
      </form>
<?php
  $html->Close();
?>
