<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'listusers':
        if($us = $db->query('select u.username, u.displayname, u.avatar, u.level, s.lastlogin, s.registered, s.fans, s.comments from users as u left join users_stats as s on s.id=u.id order by s.lastlogin desc')) {
          $ajax->Data->hasMore = false;
          $ajax->Data->users = [];
          while($u = $us->fetch_object()) {
            if(!$u->displayname)
              $u->displayname = $u->username;
            if(!$u->avatar)
              $u->avatar = t7user::DEFAULT_AVATAR;
            $u->level = t7user::LevelNameFromNumber($u->level);
            $u->lastlogin = t7format::TimeTag('ago', $u->lastlogin, 'g:i a \o\n l F jS Y');
            $u->registered = t7format::TimeTag('ago', $u->registered, 'g:i a \o\n l F jS Y');
            $ajax->Data->users[] = $u;
          }
        } else
          $ajax->Fail('error looking up user list.');
        break;
      case 'checkusername':
        if(isset($_GET['username'])) {
          $msg = t7user::CheckUsername(trim($_GET['username']), +$user->ID);
          if($msg !== true)
            $ajax->Fail($msg);
        } else
          $ajax->Fail('username missing.');
        break;
      case 'checkname':
        if(isset($_GET['name'])) {
          $msg = t7user::CheckName(trim($_GET['name']), +$user->ID);
          if($msg !== true)
            $ajax->Fail($msg);
        } else
          $ajax->Fail('name missing.');
        break;
      case 'checkemail':
        if(isset($_GET['email'])) {
          if(strtolower(substr(trim($_GET['email']), -12)) == '@example.com')
            $ajax->Fail('e-mail address is not required.  please don’t enter a fake one.');
          else if(!t7user::CheckEmail(trim($_GET['email'])))
            $ajax->Fail('doesn’t look like an e-mail address.');
        } else
          $ajax->Fail('email missing.');
        break;
      case 'register':
        if(isset($_POST['csrf']))
          if(t7auth::CheckCSRF($_POST['csrf']))
            // TODO:  add other provides to in_array
            if(isset($_SESSION['registering']) && in_array($_SESSION['registering'], ['google', 'twitter', 'facebook']) && isset($_SESSION[$_SESSION['registering']]))
              if(isset($_POST['username'])) {
                $msg = t7user::CheckUsername($_POST['username'] = trim($_POST['username']));
                if($msg === true) {
                  if(!isset($_POST['displayname']) || true !== t7user::CheckName($_POST['displayname'] = trim($_POST['displayname'])))
                    $_POST['displayname'] = '';
                  if(isset($_POST['useavatar']) && isset($_SESSION[$_SESSION['registering']]['avatar'])) {
                    // make sure the avatar url points to a readable image
                    $c = curl_init($_SESSION[$_SESSION['registering']]['avatar']);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    $avatar = curl_exec($c);
                    curl_close($c);
                    if($avatar = imagecreatefromstring($avatar))
                      imagedestroy($avatar);
                    else
                      $_SESSION[$_SESSION['registering']]['avatar'] = '/images/user.jpg';
                  } else
                    $_SESSION[$_SESSION['registering']]['avatar'] = '/images/user.jpg';
                  if(isset($_POST['website']) && ($_POST['website'] = trim($_POST['website'])) != '' && !t7format::CheckUrl($_POST['website']))
                    $_POST['website'] = '';
                  $db->autocommit(false);  // users row should only actually be created if login row is too
                  if($db->real_query('insert into users (username, displayname, avatar) values (\'' . $db->escape_string($_POST['username']) . '\', \'' . $db->escape_string($_POST['displayname']) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']]['avatar']) . '\')')) {
                    $uid = $db->insert_id;
                    $idfld = ['google' => 'sub', 'twitter' => 'user_id', 'facebook' => 'extid'];
                    if($db->real_query('insert into `login_' . $db->escape_string($_SESSION['registering']) . '` (user, ' . $idfld[$_SESSION['registering']] . ', profile) values (\'' . $db->escape_string($uid) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']][$idfld[$_SESSION['registering']]]) . '\', \'' . $db->escape_string($_SESSION[$_SESSION['registering']]['profile']) . '\')')) {
                      $db->commit();
                      $db->autocommit(true);
                      if(isset($_POST['email']) && t7user::CheckEmail($_POST['email'] = trim($_POST['email'])))
                        $db->real_query('insert into users_email (id, email) values (\'' . $db->escape_string($uid) . '\', \'' . $db->escape_string($_POST['email']) . '\')');
                      if(isset($_POST['website']) || isset($_POST['linkprofile'])) {
                        $ins = 'insert into users_profiles (id';
                        if(isset($_POST['website']) && $_POST['website'])
                          $ins .= ', website';
                        if(isset($_POST['linkprofile']))
                          $ins .= ', ' . $_SESSION['registering'];
                        $ins .= ') values (\'' . $db->escape_string($uid);
                        if(isset($_POST['website']) && $_POST['website'])
                          $ins .= '\', \'' . $db->escape_string($_POST['website']);
                        if(isset($_POST['linkprofile']))
                          $ins .= '\', \'' . $db->escape_string(t7user::CollapseProfileLink($_SESSION[$_SESSION['registering']]['profile'], $_SESSION['registering']));
                        $db->real_query($ins . '\')');
                      }
                      $db->real_query('insert into users_stats (id, registered) values (\'' . $db->escape_string($uid) . '\', \'' . time() . '\')');
                      $user->Login('register', $uid, $_SESSION[$_SESSION['registering']]['remember']);
                      $ajax->Data->continue = $_SESSION[$_SESSION['registering']]['continue'];
                      unset($_SESSION[$_SESSION['registering']]);
                      unset($_SESSION['registering']);
                    } else
                      $ajax->Fail('database error linking sign in account.');
                  } else
                    $ajax->Fail('database error registering user.');
                } else
                  $ajax->Fail($msg);
              } else
                $ajax->Fail('username is required.');
            else
              $ajax->Fail('could not find sign in account information.');
          else
            $ajax->Fail('there was a problem with the verification data.  this can happen if you wait too long on the registration form, so if that could be what happened just try again.');
        else
          $ajax->Fail('verification data missing.');
        break;
      case 'addfriend':
        if($user->IsLoggedIn())
          if(isset($_GET['friend']))
            if($db->real_query('insert into users_friends (fan, friend) values (\'' . +$user->ID . '\', \'' . +$_GET['friend'] . '\')'))
              $db->real_query('update users_stats set fans=(select count(1) from users_friends where friend=\'' . +$_GET['friend'] . '\') where id=\'' . +$_GET['friend'] . '\'');
            else
              $ajax->Fail('database error adding friend.');
          else
            $ajax->Fail('cannot add friend because there is no friend specified.');
        else
          $ajax->Fail('cannot add friend when not signed in.  if you thought you were signed in, you may have timed out and need to sign in again.');
        break;
      case 'removefriend':
        if($user->IsLoggedIn())
          if(isset($_GET['friend']))
            if($db->real_query('delete from users_friends where fan=\'' . +$user->ID . '\' and friend=\'' . +$_GET['friend'] . '\''))
              $db->real_query('update users_stats set fans=(select count(1) from users_friends where friend=\'' . +$_GET['friend'] . '\') where id=\'' . +$_GET['friend'] . '\'');
            else
              $ajax->Fail('database error removing friend.');
          else
            $ajax->Fail('cannot remove friend because there is no friend specified.');
        else
          $ajax->Fail('cannot remove friend when not signed in.  if you thought you were signed in, you may have timed out and need to sign in again.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are:  listusers, checkusername, checkname, checkemail, register, addfriend, removefriend.');
        break;
    }
    $ajax->Send();
    die;
  }

  $html = new t7html(['ko' => true]);
  $html->Open('user list');
?>
      <h1>user list</h1>

      <p class=info data-bind="visible: loadingUsers">loading user list...</p>
      <p class=info data-bind="visible: !loadingUsers() && users().length == 0">no users found</p>

      <table data-bind="visible: users().length">
        <thead><tr>
          <th>user</th>
          <th>level</th>
          <th>last login</th>
          <th>registered</th>
          <th class=number>fans</th>
          <th class=number>comments</th>
        </tr></thead>
        <tbody data-bind="foreach: users">
          <tr>
            <td><a class=profile data-bind="attr: {href: username + '/', title: 'view ' + displayname + '’s profile'}">
              <img class=avatar data-bind="attr: {src: avatar}" alt="">
              <span data-bind="text: displayname"></span>
            </a></td>
            <td data-bind="text: level"></td>
            <td><time data-bind="text: lastlogin.display + ' ago', attr: {datetime: lastlogin.datetime, title: lastlogin.title}"></time></td>
            <td><time data-bind="text: registered.display + ' ago', attr: {datetime: registered.datetime, title: registered.title}"></time></td>
            <td class=number data-bind="text: fans"></td>
            <td class=number data-bind="text: comments"></td>
          </tr>
        </tbody>
        <tfoot data-bind="visible: hasMoreUsers"><tr>
          <td colspan=7>show more users</td>
        </tr></tfoot>
      </table>
<?php
  $html->Close();
?>
