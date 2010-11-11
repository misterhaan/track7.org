<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode) {
    $page->Show404();
    die;
  }
  $page->Start('delete user');
  if($_GET['user']) {
    $uid = 'select uid from users where login=\'' . addslashes($_GET['user']) . '\' or uid=\'' . addslashes($_GET['user']). '\'';
    if($uid = $db->GetValue($uid, 'error looking up user id', 'user not found'))
      if(removeAll($db, $uid)) {
        $page->Info('successfully deleted user ' . htmlspecialchars($_GET['user'], ENT_COMPAT, _CHARSET));
        if(shiftAll($db, $uid))
          $page->Info('successfully shifted other users');
        if($nextuid = $db->GetValue('select max(uid)+1 from users'))
          if(false !== $db->Change('alter table users auto_increment=' . +$nextuid))
            $page->Info('successfully updated next user id');
      }
  }
  // show list of users who should probably be deleted
  $users = 'select u.login from users as u left join usercontact as c on c.uid=u.uid left join userstats as s on s.uid=u.uid where ' . time() . '-s.since>5270400 and s.lastlogin-s.since<60 and s.pageload-s.since<120 and u.flags=0 and u.style=1 and u.tzoffset=0 and c.flags=0 and s.signings=0 and s.comments=0 and s.posts=0 and s.discs=0 and s.rounds=0 and c.jabber is null and c.icq is null and c.aim is null order by s.since';
  if($users = $db->GetSplit($users, 35, 0, '', '', 'error looking up junk users', 'no junk users found')) {
?>
      <ul>
<?
    while($u = $users->NextRecord()) {
?>
        <li><a href="/user/<?=$u->login; ?>/"><?=$u->login; ?></a> &nbsp; <a href="?user=<?=$u->login; ?>" title="delete"><img src="/style/del.png" alt="del" /></a></li>
<?
    }
?>
      </ul>
<?
    $page->SplitLinks();
  }
  $page->End();

  function removeAll(&$db, $uid) {
    $success = remove($db, $uid, 'users');
    $success &= remove($db, $uid, 'usercontact');
    $success &= remove($db, $uid, 'userprofiles');
    $success &= remove($db, $uid, 'usersongs');
    $success &= remove($db, $uid, 'userstats');
    return $success;
  }

  function remove(&$db, $uid, $table, $field = 'uid') {
    $success = false !== $db->Change('delete from ' . $table . ' where ' . $field . '=' . $uid, 'error deleting from ' . $table);
    return $success && (false !== $db->Change('alter table ' . $table . ' order by ' . $field, 'error sorting ' . $table));
  }

  function shiftAll(&$db, $uid) {
    if(!shift($db, $uid, 'users'))
      return false;
    $success = shift($db, $uid, 'usercontact');
    $success &= shift($db, $uid, 'userprofiles');
    $success &= shift($db, $uid, 'usersongs');
    $success &= shift($db, $uid, 'userstats');
    $success &= shift($db, $uid, 'usermessages', 'fromuid');
    $success &= shift($db, $uid, 'usermessages', 'touid');
    $success &= shift($db, $uid, 'userfriends', 'fanuid');
    $success &= shift($db, $uid, 'userfriends', 'frienduid');
    $success &= shift($db, $uid, 'comments');
    $success &= shift($db, $uid, 'dgcaddy');
    $success &= shift($db, $uid, 'dgplayerstats');
    $success &= shift($db, $uid, 'dgrounds');
    $success &= shift($db, $uid, 'rpgchars');
    $success &= shift($db, $uid, 'guides', 'author');
    $success &= shift($db, $uid, 'hits');
    $success &= shift($db, $uid, 'hbposts');
    $success &= shift($db, $uid, 'hbthreads');
    $success &= shift($db, $uid, 'votes');
    return $success;
  }

  function shift(&$db, $uid, $table, $field = 'uid') {
    return false !== $db->Change('update ' . $table . ' set ' . $field . '=' . $field . '-1 where ' . $field . '>' . $uid, 'failed to shift ' . $table . '.' . $field);
  }
?>
