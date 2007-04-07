<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('track7 to-do list');

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
?>
      <ul>
        <li class="add"><a href="?addarea">add a new area</a></li>
        <li class="add"><a href="?addtask">add a new task</a></li>
      </ul>
<?
  }
?>
      <p>
        this page lists things i intend to do (eventually) on this site.&nbsp;
        once a task is complete, it will disappear from this list and show up
        (hopefully working) on the site.&nbsp; often times i will sit down and
        do one of these things in a single sitting, so it will go straight from
        new to done.
      </p>
<?
  // get list of areas
  $areas = 'select a.id, a.name, count(1) as tasks from t7taskarea as a, tasks as t where a.parent is null and t.status!=\'done\' and t.status!=\'cancelled\' and t.project=\'track7\' and t.parentarea=a.id group by a.id';
  if($areas = $db->Get($areas, 'error reading task categories', 'there is nothing left to do!'))
    while($area = $areas->NextRecord()) {
      $page->Heading($area->name . ' - ' . $area->tasks . ' task' . ($area->tasks > 1 ? 's' : '') . ($user->GodMode ? '&nbsp; <a href="?editarea=' . $area->id . '#frmeditarea"><img src="/style/edit.png" alt="edit" /></a>' : ''));
      if($_GET['editarea'] == $area->id)
        editarea($area);
      $tasks = 'select t.id, t.area, t.status, t.title, t.instant, r.rating, r.votes from tasks as t, ratings as r where r.type=\'task\' and r.selector=t.id and t.area=' . $area->id . ' and t.status!=\'done\' and t.status!=\'cancelled\' order by r.rating desc, t.instant';
      if($tasks = $db->Get($tasks, 'error reading list of things to do', '')) {
?>
      <ul>
<?
        while($task = $tasks->NextRecord())
          showtask($task);
?>
      </ul>

<?
      }
      $subareas = 'select a.id, a.name, count(1) as tasks from t7taskarea as a, tasks as t where a.parent=' . $area->id . ' and t.status!=\'done\' and t.status!=\'cancelled\' and t.area=a.id group by a.id';
      if($subareas = $db->Get($subareas, 'error reading task sub-categories', '')) {
        while($subarea = $subareas->NextRecord()) {
          $page->SubHeading($subarea->name . ' - ' . $subarea->tasks . ' task' . ($subarea->tasks > 1 ? 's' : '') . ($user->GodMode ? '&nbsp; <a href="?editarea=' . $subarea->id . '#frmeditarea"><img src="/style/edit.png" alt="edit" /></a>' : ''));
          if($_GET['editarea'] == $subarea->id)
            editarea($subarea, $area->id);
          $tasks = 'select t.id, t.status, t.title, t.instant, r.rating, r.votes from tasks as t, ratings as r where r.type=\'task\' and r.selector=t.id and t.area=' . $subarea->id . ' and t.status!=\'done\' and t.status!=\'cancelled\' order by r.rating desc, t.instant';
          if($tasks = $db->Get($tasks, 'error reading list of things to do', '')) {
?>
      <ul>
<?
            while ($task = $tasks->NextRecord())
              showtask($task);
?>
      </ul>

<?
          }
        }
      }
    }
  $page->End();

/*---------------------------------------------------------------[ showtask ]-*/
  function showtask($task) {
    global $db, $user, $page;
?>
        <li>
<?
    if($_GET['edit'] == $task->id && $user->GodMode) {
      require_once 'auForm.php';
      $edit = new auForm('edittask', '?edit=' . $task->id);
      $editset = new auFormFieldSet('edit task');
      $areas = 'select id, name from t7taskarea';
      if($areas = $db->Get($areas, 'error looking up task areas', 'no task areas found', true))
        while($area = $areas->NextRecord())
          $arealist[$area->id] = $area->name;
      $editset->AddField('title', 'title', 'edit the title of this task', true, $task->title, _AU_FORM_FIELD_NORMAL, 60, 255);  
      $editset->AddSelect('area', 'area', 'choose the area this task belongs in', $arealist, $task->area);
      $editset->AddSelect('status', 'status', 'choose the status of this task', auFormSelect::ArrayIndex(array('new', 'started', 'done', 'cancelled')), $task->status);
      $editset->AddButtons('edit', 'save changes to this task');
      $edit->AddFieldSet($editset);
      if($edit->CheckInput(true)) {
        $task->title = htmlentities($_POST['title']);
        $task->status = $_POST['status'];
        if($task->area != $_POST['area']) {
          $task->area = $_POST['area'];
          $parentarea = 'select parent from t7taskarea where id=\'' . $task->area . '\'';
          $parentarea = $db->GetValue($parentarea, 'error looking up parent area', 'area not found', true);
          if($parentarea == null)
            $parentarea = $task->area;
          $update = 'update tasks set area=\'' . $task->area . '\', parentarea=\'' . $parentarea . '\', title=\'' . addslashes($task->title) . '\', status=\'' . addslashes($_POST['status']) . '\' where id=' . $_GET['edit'];
        } else
          $update = 'update tasks set title=\'' . addslashes($task->title) . '\', status=\'' . addslashes($_POST['status']) . '\' where id=' . $_GET['edit'];
        if(false !== $db->Change($update, 'error updating task'))
          $page->Info('task updated successfully');
      } else
        $edit->WriteHTML(true);
    }
?>
          <?=$task->title . ($_GET['edit'] != $task->id && $user->GodMode ? '&nbsp; <a class="img" title="edit this task" href="?edit=' . $task->id . '#frmedittask"><img src="/style/edit.png" alt="edit" /></a>' : ''); ?>
<?
    if($_GET['vote'] == $task->id) {
      $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'task\' and r.selector=\'' . $task->id . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
      if(false === ($rating = $db->GetValue($rating, 'error checking to see if you have already voted', '')))
        $rating = 0;
      require_once 'auForm.php';
      $vote = new auForm('vote', '?vote=' . $task->id);
      $vote->AddData('task', $task->id);
      $voteset = new auFormFieldSet('rate task');
      $voteset->AddSelect('vote', 'rating', 'choose your rating of this task', array(-3 => '-3 (don\'t want)', -2 => '-2', -1 => '-1', 0 => '0 (don\'t care)', 1 => '1', 2 => '2', 3 => '3 (want)'), +$rating);
      $voteset->AddButtons('vote', 'cast your vote for this task');
      $vote->AddFieldSet($voteset);
      if($vote->CheckInput($user->Valid)) {
        $ratingid = 'select id from ratings where type=\'task\' and selector=\'' . addslashes($_POST['task']) . '\'';
        if($ratingid = $db->GetValue($ratingid, 'error looking up rating id', 'no rating found')) {
          $vote = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
          if(false !== $db->Put($vote, 'error adding vote')) {
            $rating = 'select sum(vote) ratesum, count(1) as ratecnt from votes where ratingid=' . $ratingid;
            if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
              $task->rating = $rating->ratesum / $rating->ratecnt;
              $task->votes = $rating->ratecnt;
              $rating = 'update ratings set rating=' . $task->rating . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
              if(false !== $db->Change($rating, 'error updating new rating')) {
                $page->Info('vote sucessfully added or updated');
                unset($_GET['vote']);
              }
            }
          }
        }
      }
    }
?>
          <div class="details">
            <?=$task->status; ?>;
            rated <?=$task->rating; ?> (<?=$task->votes; ?> vote<?=$task->votes == 1 ? '' : 's'; ?><?=$_GET['vote'] == $task->id ? '' : ' <a href="?vote=' . $task->id . '#frmvote">cast vote</a>'; ?>);
            added <?=strtolower($user->tzdate('M j, Y', $task->instant)); ?>
          </div>
<?
    if($_GET['vote'] == $task->id)
      $vote->WriteHTML($user->Valid);
?>
        </li>
<?
  }

/*---------------------------------------------------------------[ editarea ]-*/
  function editarea($area, $parentid = 'null') {
    global $db, $page;
    require_once 'auForm.php';
    $editarea = new auForm('editarea', '?editarea=' . $area->id);
    $editareaset = new auFormFieldSet('edit area');
    $editareaset->AddField('name', 'name', 'enter the name of this area', true, $area->name, _AU_FORM_FIELD_NORMAL, 30, 255);
    $parentlist['null'] = '(none)';
    $parents = 'select id, name from t7taskarea where parent is null';
    if($parents = $db->Get($parents, 'error looking up parent areas', ''))
      while($parent = $parents->NextRecord())
        $parentlist[$parent->id] = $parent->name;
    $editareaset->AddSelect('parent', 'parent', 'choose the area this area belongs under', $parentlist, $parentid);
    $editareaset->AddButtons('save', 'save changes to this area');
    $editarea->AddFieldSet($editareaset);
    if($editarea->CheckInput(true)) {
      $update = 'update t7taskarea set name=\'' . addslashes(htmlentities($_POST['name'])) . '\', parent=' . addslashes($_POST['parent']) . ' where id=\'' . addslashes($area->id) . '\'';
      if(false !== $db->Change($update, 'error updating area'))
        $page->Info('area updated successfully');
    } else
      $editarea->WriteHTML(true);
  }
?>
