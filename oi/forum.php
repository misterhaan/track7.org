<?
  //$getvars = array('fid');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';
  require_once 'auForm.php';

  if(!isset($_GET['fid']) || !is_numeric($_GET['fid']))
    $page->Show404();

  $forum = 'select title from oiforums where id=' . $_GET['fid'];
  if((isset($_GET['edit']) || $_POST['formid'] == 'editforum') && $user->GodMode)
    $forum = 'select title, description, gid, sort from oiforums where id=' . $_GET['fid'];
  if($forum = $db->GetRecord($forum, 'error looking up forum title', 'unable to find a forum with id ' . $_GET['fid'], true)) {
    // fix counts on this forum if admin asked for it
    if(isset($_GET['fix']) && $user->GodMode) {
      $stat = 'select count(1) as threads, sum(posts) as posts from oithreads where fid=' . $_GET['fid'];
      if($stat = $db->GetRecord($stat, 'error looking up thread and post count for this forum', 'strange--there seems to be nothing there!')) {
        if($stat->threads < 1)
          $stat->threads = 0;
        if($stat->posts < 1)
          $stat->posts = 0;
        $stat = 'update oiforums set threads=' . $stat->threads . ', posts=' . $stat->posts . ' where id=' . $_GET['fid'];
        if(false !== $db->Change($stat, 'error updating thread and post count for this forum'))
          $page->Info('thread and post count fixed successfully');
      }
    }

    // process edit forum form if submitted by admin
    if($_POST['form'] == 'editforum' && $user->GodMode) {
      if(strlen($_POST['sort']) < 1 || !is_numeric($_POST['sort']))
        $_POST['sort'] = $forum->sort;
      if(strlen($_POST['gid']) < 1 || !is_numeric($_POST['gid']))
        $_POST['gid'] = $forum->gid;
      // shift forums up where this forum came from
      $shift = 'update oiforums set sort=sort-1 where sort>' . $forum->sort . ' and gid=' . $forum->gid;
      $db->Change($shift, 'error shifting forums in old group');
      // shift forums down where this forum is going
      $shift = 'update oiforums set sort=sort+1 where sort>=' . $_POST['sort'] . ' and gid=' . $_POST['gid'];
      $db->Change($shift, 'error shifting forums in new group');
      $update = 'update oiforums set title=\'' . addslashes($_POST['title']) . '\', description=\'' . addslashes($_POST['description']) . '\', sort=' . $_POST['sort'] . ', gid=' . $_POST['gid'] . ' where id=' . $_GET['fid'];
      if(false !== $db->Change($update, 'error saving changes')) {
        $page->Info('forum updated successfully');
        $forum->title = $_POST['title'];
      }
    }
    $page->Start($forum->title . ' thread listing - oi', $forum->title . ($user->GodMode ? ' &nbsp; <a class="img" href="/oi/f' . $_GET['fid'] . '/fix"><img src="/fix.png" alt="fix" /></a> <a class="img" href="/oi/f' . $_GET['fid'] . '/edit" title="edit this forum"><img src="/style/edit.png" alt="edit" /></a> <a class="img" href="/oi/f' . $_GET['fid'] . '/del" title="delete this forum"><img src="/style/del.png" alt="del" /></a>' : ''), 'thread listing');
    if(isset($_GET['del']) && $user->GodMode) {
      $del = 'delete from oiforums where id=' . $_GET['fid'];
      if(false !== $db->Change($del, 'error deleting forum'))
        $page->Info('forum deleted successfully');
      $page->End();
      die;
    }
    if(isset($_GET['edit']) && $user->GodMode) {
      $form = new auForm('editforum', '/oi/f' . $_GET['fid'] . '/');
      $formset = new auFormFieldSet('edit forum');
      $formset->AddField('title', 'title', 'the title of the forum will be used to link to the thread listing', true, $forum->title, _AU_FORM_FIELD_NORMAL, 40, 255);
      $formset->AddField('description', 'description', 'the description of the forum will display in the forum listing', false, $forum->description, _AU_FORM_FIELD_NORMAL, 60, 255);
      $formset->AddField('sort', 'sort', 'a number used to decide which order the forums show up in', false, $forum->sort, _AU_FORM_FIELD_NORMAL, 2, 2);
      $groups = 'select id, title from oigroups order by sort';
      if($groups = $db->Get($groups, '', ''))
        while($group = $groups->NextRecord())
          $groupvalues[$group->id] = $group->title;
      $formset->AddSelect('gid', 'group', 'choose the group this forum belongs in', $groupvalues, $forum->gid);
      $formset->AddButtons('save', 'save changes to this forum');
      $form->AddFieldSet($formset);
      $form->WriteHTML();
    }
?>
      <p><a href="<?=dirname($_SERVER['PHP_SELF']); ?>/f<?=$_GET['fid']; ?>/post">post a new thread</a></p>
<?

    $threads = 'select t.id, t.title, t.instant, t.posts, t.lastpost, u.login, p.instant as pinstant, p.subject, pu.login as plogin from oithreads as t left join users as u on t.uid=u.uid left join oiposts as p on t.lastpost=p.id left join users as pu on p.uid=pu.uid where t.fid=' . $_GET['fid'] . ' order by pinstant desc';
    if($threads = $db->GetSplit($threads, 20, 0, '', '', 'error reading threads for this forum', 'there are currently no threads in this forum', false, true)) {
?>
      <table class="text" id="oithreadlist" cellspacing="0">
        <thead class="minor"><tr><th>thread</th><th>posts</th><th>started</th><th>last post</th></tr></thead>
        <tbody>
<?
      while($thread = $threads->NextRecord()) {
        if(strlen($thread->subject) > 16)
          $thread->subject = substr($thread->subject, 0, 15) . '...';
?>
          <tr><td><a href="/oi/f<?=$_GET['fid']; ?>/t<?=$thread->id; ?>/"><?=$thread->title; ?></a></td><td class="number"><?=$thread->posts; ?></td><td class="detail"><?=$user->tzdate('Y-m-d g:i a', $thread->instant) . ' by ' . ($thread->login ? $thread->login : 'anonymous'); ?></td><td class="detail"><a href="/oi/f<?=$_GET['fid']; ?>/t<?=$thread->id; ?>/#p<?=$thread->lastpost; ?>"><?=$thread->subject; ?></a> <?=$user->tzdate('Y-m-d g:i a', $thread->pinstant) . ' by ' . ($thread->plogin ? $thread->plogin : 'anonymous'); ?></td></tr>
<?
      }
?>
        </tbody>
        <tfoot><tr><td colspan="4">
<?
      $page->SplitLinks();
?>
        </td></tr></tfoot>
      </table>

<?
    }
?>
      <p><a href="<?=dirname($_SERVER['PHP_SELF']); ?>/f<?=$_GET['fid']; ?>/post">post a new thread</a></p>
<?
  } else
    $page->Start('(unknown) thread listing - oi', '(unknown forum)', 'thread listing');
  $page->End();
?>
