<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->Valid) {
    $page->Start('friends');
    $page->Error('sorry, only users can have friends!');
?>
      <p>
        you need to be logged in before you can add users to your friends list
        or remove users from your friends list.&nbsp; if you have an account,
        use the login form to the left and try again.&nbsp; if you don't have an
        account, you will need to <a href="register.php">register</a> for one.
      </p>
<?
    $page->End();
    die;
  }
  if(strlen($_GET['remove'])) {
    $friend = 'select uid from users where login=\'' . addslashes($_GET['remove']) . '\' or uid=\'' . addslashes($_GET['remove']) . '\'';
    if(false !== $friend = $db->GetValue($friend, 'error looking up user to remove from friend list', 'unable to find user to remove from friend list')) {
      $del = 'delete from userfriends where fanuid=\'' . $user->ID . '\' and frienduid=\'' . $friend . '\'';
      if(false !== $db->Change($del, 'error removing friend from list')) {
        if($friend != $user->ID) {
          $update = 'update userstats set fans=fans-1 where uid=\'' . $friend . '\'';
          $db->Change($update);
        }
        if(substr($_GET['from'], 0, 1) == '/')
          $url = htmlspecialchars($_GET['from'], ENT_COMPAT, _CHARSET);
        else
          $url = $_SERVER['PHP_SELF'];
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $url);
        die;
      }
    }
  } elseif(strlen($_GET['add'])) {
    $friend = 'select uid from users where login=\'' . addslashes($_GET['add']) . '\' or uid=\'' . addslashes($_GET['add']) . '\'';
    if(false !== $friend = $db->GetValue($friend, 'error looking up user to add to friend list', 'unable to find user to add to friend list')) {
      $ins = 'insert into userfriends (fanuid, frienduid) values (\'' . $user->ID . '\', \'' . $friend . '\')';
      if(false !== $db->Put($ins, 'error adding friend', '')) {
        if($friend != $user->ID) {
          $update = 'update userstats set fans=fans+1 where uid=\'' . $friend . '\'';
          $db->Change($update);
        }
        if(substr($_GET['from'], 0, 1) == '/')
          $url = htmlspecialchars($_GET['from'], ENT_COMPAT, _CHARSET);
        else
          $url = $_SERVER['PHP_SELF'];
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $url);
        die;
      }
    }
  }
  $page->Start('friends');
?>
      <p>
        your friends, if you have any, are listed below.&nbsp; marking others as
        friends increases their fan count and puts links to their profiles on
        your profile page for only you to see.&nbsp; there's no way for your
        friends to find out that you marked them as a friend.&nbsp; eventually,
        there will be more notification options that will use your friend list.
      </p>
<?
  $friends = 'select u.login from userfriends as f left join users as u on u.uid=f.frienduid where fanuid=\'' . $user->ID . '\' order by login';
  if($friends = $db->Get($friends, 'error looking up friends', '')) {
?>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>name</th><th>profile</th><th>send</th><th>remove</th></tr></thead>
        <tbody>
<?
    while($friend = $friends->NextRecord()) {
?>
          <tr><td><?=$friend->login; ?></td><td><a href="/user/<?=$friend->login; ?>">profile</a></td><td><a href="/user/sendmessage.php?to=<?=$friend->login; ?>">send</a></td><td><a href="/user/friends.php?remove=<?=$friend->login; ?>">remove</a></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
  }
  require_once 'auForm.php';
  $addfriend = new auForm('addfriend', '', 'get');
  $addfriendset = new auFormFieldSet('add a user as a friend');
  $addfriendset->AddField('add', 'username', 'enter the name of a track7 user to add them to your friend list', true, '', _AU_FORM_FIELD_NORMAL, 20, 32);
  $addfriendset->AddButtons('add', 'add this user to your friend list', '');
  $addfriend->AddFieldSet($addfriendset);
  $addfriend->WriteHTML(true);
  $page->End();
?>