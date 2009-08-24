<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($user->GodMode && isset($_GET['new'])) {
    $new = getTaskForm($db);
    if($new->CheckInput(true) && $area = getArea($db, $_POST['area'])) {
      $ins = 'insert into tasks (instant, status, area, parentarea, title) values (' . time() . ', \'' . addslashes($_POST['status']) . '\', \'' . $area->id . '\', \'' . ($area->parent === null ? $area->id : $area->parent) . '\', \'' . addslashes(htmlspecialchars($_POST['title'], ENT_COMPAT, _CHARSET)) . '\')';
      $ins = $db->Put($ins, 'error saving task');
      if(false !== $ins) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/todo.php?id=' . $ins);
        die;
      }
    }
    $page->Start('add task');
    $new->WriteHTML(true);
    $page->End();
    die;
  }
  if(is_numeric($_GET['id'])) {
    $_GET['id'] = +$_GET['id'];
    $task = 'select t.id, t.instant, t.status, t.title, a.id as aid, a.name as area, p.name as parentarea, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from tasks as t left join t7taskarea as a on a.id=t.area left join t7taskarea as p on p.id=t.parentarea left join ratings as r on r.type=\'task\' and r.selector=t.id left join votes as v on v.ratingid=r.id and ' . ($user->Valid ? 'v.uid=' . $user->ID : 'v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\'') . ' where t.project=\'track7\' and t.id=\'' . $_GET['id'] . '\'';
    if($task = $db->GetRecord($task, 'error looking up task', 'task not found')) {
      if($user->GodMode && isset($_GET['edit'])) {
        $edit = getTaskForm($db, $task);
        if($edit->CheckInput(true) && $area = getArea($db, $_POST['area'])) {
          $update = 'update tasks set status=\'' .addslashes($_POST['status'])  . '\', area=\'' . $area->id . '\', parentarea=\'' . ($area->parent === null ? $area->id : $area->parent) . '\', title=\'' . addslashes(htmlspecialchars($_POST['title'], ENT_COMPAT, _CHARSET)) . '\' where id=\'' . $_GET['id'] . '\'';
          if(false !== $db->Change($update, 'error saving task')) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . '/todo.php?id=' . $_GET['id']);
            die;
          }
        }
        $page->Start('edit task');
        $edit->WriteHTML(true);
        $page->End();
        die;
      }
      $page->Start($task->title . ' - track7 to-do list', $task->title);
?>
      <table class="columns" cellspacing="0">
        <tr><th>status</th><td colspan="2"><?=$task->status; ?></td></tr>
        <tr><th>rating</th><td><? auRating::Show('task', $task->id, $task->rating, $task->votes, $task->vote); ?></td><td width="100%"></td></tr>
        <tr><th>area</th><td colspan="2"><?=$task->area; ?><?=($task->parentarea != $task->area ? ' (' . $task->parentarea . ')' : ''); ?></td></tr>
        <tr><th>age</th><td colspan="2"><?=auText::HowLongAgo($task->instant); ?></td></tr>
      </table>

      <ul>
<?
      if($user->GodMode) {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;edit">edit this task</a></li>
<?
      }
?>
        <li><a href="todo.php">back to list</a></li>
      </ul>
<?
      $page->End();
      die;
    }
  }
  $page->Start('track7 to-do list');
?>
      <p>
        this page lists things i intend to do (eventually) on this site.&nbsp;
        once a task is complete, it will disappear from this list and show up
        (hopefully working) on the site.
      </p>

<?
  if($user->GodMode) {
?>
      <ul>
        <li><a href="?new">new task</a></li>
      </ul>

<?
  }
  $tasks = 'select t.id, t.instant, t.status, t.title, a.name as area, p.name as parentarea, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from tasks as t left join t7taskarea as a on a.id=t.area left join t7taskarea as p on p.id=t.parentarea left join ratings as r on r.type=\'task\' and r.selector=t.id left join votes as v on v.ratingid=r.id and ' . ($user->Valid ? 'v.uid=' . $user->ID : 'v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\'') . ' where t.project=\'track7\' and (t.status=\'new\' or t.status=\'started\') order by t.status desc, rating desc, votes desc, t.area, t.instant';
  if($tasks = $db->Get($tasks, 'error looking up current tasks', 'no current tasks found')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>status</th><th>rating</th><th>title</th><th>area</th><th>age</th></tr></thead>
        <tbody>
<?
    while($task = $tasks->NextRecord()) {
      echo '          <tr id="task';
      echo $task->id;
      echo '"><td>';
      echo $task->status;
      echo '</td><td>';
      auRating::Show('task', $task->id, $task->rating, $task->votes, $task->vote);
      echo '</td><td>';
      echo '<a href="?id=' . $task->id . '">' . $task->title . '</a>';
      echo '</td><td>';
      echo $task->area;
      if($task->parentarea != $task->area)
        echo ' (' . $task->parentarea . ')';
      echo '</td><td>';
      echo auText::HowLongAgo($task->instant);
      echo '</td></tr>';
    }
?>
        </tbody>
      </table>

<?
  }
  if($user->GodMode) {
    if(isset($_GET['addarea'])) {
      require_once 'auForm.php';
      $addarea = new auForm('addarea', '?addarea');
      $addareaset = new auFormFieldSet('add new area');
      $parents = 'select id, name from t7taskarea where parent is null';
      $parentchoices['null'] = '(none)';
      if($parents = $db->Get($parents, 'error looking up parent areas', ''))
        while($parent = $parents->NextRecord())
          $parentchoices[$parent->id] = $parent->name;
      $addareaset->AddSelect('parent', 'parent', 'choose a parent area if this is a subarea', $parentchoices);
      $addareaset->AddField('name', 'name', 'enter a name for this area', true, '', _AU_FORM_FIELD_NORMAL, 30, 255);
      $addareaset->AddButtons('add', 'add this area');
      $addarea->AddFieldSet($addareaset);
      if($addarea->CheckInput(true)) {
        $ins = 'insert into t7taskarea (name, parent) values (\'' . addslashes(htmlentities($_POST['name'])) . '\', ' . addslashes($_POST['parent']) . ')';
        if(false !== $db->Put($ins, 'error adding new area'))
          $page->Info('new area added successfully');
      } else
        $addarea->WriteHTML(true);
    } elseif(isset($_GET['addtask'])) {
      require_once 'auForm.php';
      $addtask = new auForm('addtask', '?addtask');
      $addtaskset = new auFormFieldSet('add new task');
      $areas = 'select id, name from t7taskarea where parent is null';
      if($areas = $db->Get($areas, 'error looking up task areas', 'no task areas found', true)) {
        while($area = $areas->NextRecord())
          $areachoices[$area->id] = $area->name;
        $subareas = 'select a.id, a.name, p.name as parent from t7taskarea as a, t7taskarea as p where a.parent=p.id';
        if($subareas = $db->Get($subareas, 'error looking up task subareas', ''))
          while($subarea = $subareas->NextRecord())
            $areachoices[$subarea->id] = $subarea->name . ' (' . $subarea->parent . ')';
      }
      $addtaskset->AddSelect('area', 'area', 'choose the area this task belongs to', $areachoices);
      $addtaskset->AddField('title', 'title', 'enter the title for this task', true, '', _AU_FORM_FIELD_NORMAL, 60, 255);
      $addtaskset->AddButtons('add', 'add this task to the to-do list');
      $addtask->AddFieldSet($addtaskset);
      if($addtask->CheckInput(true)) {
        $parentarea = 'select parent from t7taskarea where id=\'' . addslashes($_POST['area']) . '\'';
        if(false !== $parentarea = $db->GetValue($parentarea, 'error checking for parent area', 'task area not found', true)) {
          if($parentarea === null)
            $parentarea = addslashes($_POST['area']);
          $ins = 'insert into tasks (instant, area, title, parentarea) values (' . time() . ', ' . $_POST['area'] . ', \'' . addslashes(htmlentities($_POST['title'])) . '\', ' . $parentarea . ')';
          if(false !== $id = $db->Put($ins, 'error adding new task')) {
            $ins = 'insert into ratings (type, selector) values (\'task\', ' . $id . ')';
            $db->Put($ins, 'error initializing rating');
            $page->Info('new task added successfully');
          }
        }
      } else
        $addtask->WriteHTML(true);
    }
  }
  $page->End();

  /**
   * Creates and returns a task edit form.
   * @param auDB $db Database connection.
   * @param object $task Task to edit in the form, or false to create a new task.
   * @return auForm Task edit form.
   */
  function getTaskForm(&$db, $task = false) {
    $form = new auForm('task', $task ? '?id=' . $task->id . '&edit' : '?new');
    $form->Add(new auFormString('title', 'title', 'enter the title of this task', true, $task->title, 100, 250));
    $form->Add(new auFormSelect('status', 'status', 'select the status of this task', true, auFormSelect::ArrayIndex(array('new', 'started', 'done', 'cancelled')), $task->status));
    $areas = 'select id, name from t7taskarea order by name';
    if($areas = $db->Get($areas, 'error looking up task areas', 'no task areas found')) {
      while($area = $areas->NextRecord())
        $arealist[$area->id] = $area->name;
      $form->Add(new auFormSelect('area', 'area', 'select the area of the site this task affects', true, $arealist, $task ? $task->aid : 1));
    }
    $form->Add(new auFormButtons('save'));
    return $form;
  }

  function getArea(&$db, $id) {
    return $db->GetRecord('select id, name, parent from t7taskarea where id=\'' . addslashes($id) . '\'', 'error looking up area', 'area not found');
  }
?>
