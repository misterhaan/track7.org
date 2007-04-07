<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';
  require_once 'auForm.php';

  $page->Start('forum listing - oi', 'oi - forum listing' . ($user->GodMode ? ' &nbsp; <a class="img" href="/oi/?groupadd" title="add a new forum group"><img src="/style/new.png" alt="new" /></a>': ''));

  // process any forms admin may have submitted
  if(isset($_GET['del']) && $user->GodMode) {
    if(!is_numeric($_GET['del']))
      $page->Error('cannot delete group with non-numeric id');
    else {
      $empty = 'select count(1) as c from oiforums where gid=' . $_GET['del'];
      if(false !== $empty = $db->GetRecord($empty, 'error checking to make sure group is empty')) {
        if($empty->c > 0)
          $page->Error('cannot delete non-empty group');
        else {
          $del = 'delete from oigroups where id=' . $_GET['del'];
          if($db->Change($del, 'error deleting group'))
            $page->Info('group deleted successfully');
        }
      }
    }
  }
  if(isset($_POST['formid']) && $user->GodMode) {
    switch($_POST['formid']) {
      // add a new group
      case 'addgroup':
        $maxsort = 'select sort from oigroups order by sort desc';
        if($maxsort = $db->GetLimit($maxsort, 0, 1, 'error finding maximum sort value')) {
          if($maxsort->NumRecords() < 1)
            $maxsort = 0;
          else {
            $maxsort = $maxsort->NextRecord();
            $maxsort = $maxsort->sort;
          }
          // empty sort means put it at the end
          if(strlen($_POST['sort']) < 1 || !is_numeric($_POST['sort']) || $_POST['sort'] > $maxsort)
            $_POST['sort'] = $maxsort + 1;
          else {
            // shift things to make room
            $shift = 'update oigroups set sort=sort+1 where sort>=' . $_POST['sort'];
            $db->Change($shift, 'error shifting other groups to make room');
          }
          $ins = 'insert into oigroups (sort, title) values (' . $_POST['sort'] . ', \'' . addslashes($_POST['title']) . '\')';
          if(false !== $db->Put($ins, 'error saving new group'))
            $page->Info('group successfully added');
        }
        break;
      // edit an existing group
      case 'editgroup':
        if(!is_numeric($_POST['id']))
          $page->Error('cannot edit group with non-numeric id!');
        else {
          $oldsort = 'select sort from oigroups where id=' . $_POST['id'];
          if($oldsort = $db->GetValue($oldsort, 'error finding previous sort value for this group', 'group not found!', true)) {
            if(strlen($_POST['sort']) < 1)
              $_POST['sort'] = $oldsort;
            elseif($_POST['sort'] > $oldsort) {
              $maxsort = 'select sort from oigroups order by sort desc';
              if($maxsort = $db->GetValue($maxsort, 'error finding maximum sort value')) {
                if($_POST['sort'] > $maxsort)
                  $_POST['sort'] = $maxsort;
              }
              $shift = 'update oigroups set sort=sort-1 where sort>' . $oldsort . ' and sort<=' . $_POST['sort'];
              $db->Change($shift, 'error shifting sort order of other groups');
            } elseif($_POST['sort'] < $oldsort) {
              $shift = 'update oigroups set sort=sort+1 where sort<' . $oldsort . ' and sort>=' . $_POST['sort'];
              $db->Change($shift, 'error shifting sort order of other groups');
            }
            $update = 'update oigroups set title=\'' . addslashes($_POST['title']) . '\', sort=' . $_POST['sort'];
            if($db->Change($update, 'error updating group:'))
              $page->Info('group updated successfully');
          }
        }
        break;
      // add a new forum
      case 'addforum':
        if(!is_numeric($_POST['gid']))
          $page->Error('invalid group id');
        else {
          $maxsort = 'select sort from oiforums where gid=' . $_POST['gid'] . ' order by sort desc';
          if($maxsort = $db->GetLimit($maxsort, 0, 1, 'error finding maximum sort value')) {
            if($maxsort->NumRecords() < 1)
              $maxsort = 1;
            else {
              $maxsort = $maxsort->NextRecord();
              $maxsort = $maxsort->sort + 1;
            }
            if(is_numeric($_POST['sort'])) {
              if($_POST['sort'] >= $maxsort)
                $_POST['sort'] = $maxsort;
              else {
                if($_POST['sort'] < 1)
                  $_POST['sort'] = 1;
                $shift = 'update oiforums set sort=sort+1 where gid=' . $_POST['gid'] . ' and sort>=' . $_POST['sort'];
                $db->Change($shift, 'error shifting sort order to make room for this forum');
              }
            } else
              $_POST['sort'] = $maxsort;
            $ins = 'insert into oiforums (gid, sort, title, description) values (' . $_POST['gid'] . ', ' . $_POST['sort'] . ', \'' . TEXT::slash($_POST['title']) . '\', \'' . TEXT::slash($_POST['description']) . '\')';
            if(false !== $db->Put($ins, 'error adding new forum'))
              $page->Info('new forum added successfully');
          }
        }
        break;
    }
  }

  // show form to add a group if admin asked for it
  if(isset($_GET['groupadd']) && $user->GodMode) {
    groupform();
  }
?>
      <p><a href="recentposts.php">recent posts in all forums</a></p>

<?
  $groups = 'select id, sort, title from oigroups order by sort';
  if($groups = $db->Get($groups, 'error reading forum groups', 'there are currently no forum groups')) {
    while($group = $groups->NextRecord()) {
      if(isset($_GET['edit']) && $_GET['edit'] == $group->id && $user->GodMode) {
        groupform($group);
      } else {
        if($user->GodMode)
          $page->Heading($group->title . groupadmin($group->id));
        else
          $page->Heading($group->title);
      }
      $forums = 'select f.id, f.title, f.description, f.threads, f.posts, f.lastpost, p.subject, p.instant, p.tid, u.login from oiforums as f, oiposts as p left join users as u on p.uid=u.uid where (f.lastpost is null or f.lastpost=p.id) and f.gid=' . $group->id . ' group by f.id order by f.sort';
      if($forums = $db->Get($forums, 'error reading forums for this group', 'there are currently no forums in this group')) {
?>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>forum</th><th>threads</th><th>posts</th><th>last post</th></tr></thead>
        <tbody>
<?
        while($forum = $forums->NextRecord()) {
          if($forum->lastpost)
            if(strlen($forum->subject) > 16)
              $forum->subject = substr($forum->subject, 0, 15) . '...';
            else
              $forum->subject .= ',';
?>
          <tr><td><a href="f<?=$forum->id; ?>/"><?=$forum->title; ?></a><div class="oifdesc"><?=$forum->description; ?></div></td><td class="number"><?=$forum->threads; ?></td><td class="number"><?=$forum->posts; ?></td><td class="detail"><?=($forum->lastpost ? '<a href="f' . $forum->id . '/t' . $forum->tid . '/#p' . $forum->lastpost . '">' . $forum->subject . '</a> by ' . ($forum->login ? $forum->login : 'anonymous') . ' ' . auText::HowLongAgo($forum->instant) . ' ago' : '(none)'); ?></td></tr>
<?
        }
?>
        </tbody>
      </table>
<?
      }
      if(isset($_GET['add']) && $_GET['add'] == $group->id && $user->GodMode) {
        $form = new auForm('addforum');
        $form->AddData('gid', $group->id);
        $formset = new auFormFieldSet('add a new forum to ' . $group->title);
        $formset->AddField('title', 'title', 'the title of the forum will be used to link to the thread listing', true, '', _AU_FORM_FIELD_NORMAL, 40, 255);
        $formset->AddField('description', 'description', 'the description of the forum will display in the forum listing', false, '', _AU_FORM_FIELD_NORMAL, 60, 255);
        $formset->AddField('sort', 'sort', 'a number used to decide which order the forums show up in', false, '', _AU_FORM_FIELD_NORMAL, 2, 2);
        $formset->AddButtons('save', 'create this forum');
        $form->AddFieldSet($formset);
        $form->WriteHTML($user->Valid);
      }
    }
  }

  $page->End();

  //------------------------------------------------------------[ groupadmin ]
  function groupadmin($id) {
    return ' &nbsp; <a class="img" href="/oi/?add=' . $id . '" title="add a forum to this group"><img src="/style/new.png" alt="new" /></a> <a class="img" href="/oi/?edit=' . $id . '" title="edit this group"><img src="/style/edit.png" alt="edit" /></a> <a class="img" href="/oi/?del=' . $id . '" title="delete this group (only if empty)"><img src="/style/del.png" alt="del" /></a>';
  }

  //-------------------------------------------------------------[ groupform ]
  function groupform($group = false) {
    $form = new auForm(($group ? 'edit' : 'add') . 'group');
    if($group)
      $form->AddData('id', $group->id);
    $formset = new auFormFieldSet(($group ? 'edit' : 'add') . ' forum group');
    $formset->AddField('title', 'title', 'the title will display as a heading for forums in this group', true, '', _AU_FORM_FIELD_NORMAL, 40, 255);
    $formset->AddField('sort', 'sort', 'a number used to decide which order the groups show up in', false, '', _AU_FORM_FIELD_NORMAL, 2, 2);
    $formset->AddButtons('save', ($group ? 'save changes to this group' : 'create this group'));
    $form->AddFieldSet($formset);
    $form->WriteHTML();
  }
?>
