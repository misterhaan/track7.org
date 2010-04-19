<?
/*----------------------------------------------------------------------------*\
 | purpose:  edit profile of currently logged-in user.                        |
 |                                                                            |
\*----------------------------------------------------------------------------*/
  define('AVATAR_SIZE', 64);
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';

  if($user->GodMode && isset($_GET['user'])) {
    $u = $_GET['user'];
    $querystring = '?user=' . $_GET['user'] . '&amp;';
  }
  elseif($user->Valid) {
    $u = $user->Name;
    $querystring = '?';
  }
  $u = $db->GetRecord('select * from users where login=\'' . addslashes($u) . '\'', '', '');
  if($u) {
    $page->Start('edit profile', 'edit profile for ' . $u->login);
    if(!isset($_GET['tab']))
      $_GET['tab'] = 'profile';
    switch($_GET['tab']) {
      case 'profile':
        require_once 'auFile.php';
        $profile = 'select avatar, signature, location, geekcode, hackerkey from userprofiles where uid=' . $u->uid;
        $profile = $db->GetRecord($profile, 'error reading user profile', 'user profile not found');
        if($_POST['submit'] == 'update') {
          $newavatar = '';
          $upload = auFile::SaveUploadImage('avatar', _ROOT . '/user/avatar/', _AU_FILE_IMAGE_PNG | _AU_FILE_IMAGE_JPEG, $u->login . '.ext', AVATAR_SIZE, AVATAR_SIZE);
          if(file_exists($_FILES['avatar']['tmp_name']))
            @unlink($_FILES['avatar']['tmp_name']);
          if($upload['found'])
            if(!$upload['saved'])
              if($profile->avatar)
                if(file_exists(_ROOT . '/user/avatar/' . $u->login . '.' . $profile->avatar))
                  $page->Error('unable to save avatar (previous avatar will remain in use)', strtolower($upload['message']));
                else {
                  $db->Change('update userprofiles set avatar=null where uid=' . $u->uid);
                  $page->Error('unable to save avatar (previous avatar lost)', strtolower($upload['message']));
                }
              else
                $page->Error('unable to save avatar', strtolower($upload['message']));
            else {
              chmod($upload['path'] . $upload['file'], 0644);
              if($profile->avatar)
                $page->Info('new avatar saved successfuly.&nbsp; this page may not show the new image yet though.');
              $newavatar = explode('.', $upload['file']);
              $newavatar = $newavatar[count($newavatar) - 1];
              if($newavatar == $profile->avatar)
                $newavatar = '';
              else {
                @unlink(_ROOT . '/user/avatar/' . $u->login . '.' . $profile->avatar);
                $profile->avatar = $newavatar;
                $newavatar = 'avatar=\'' . $newavatar . '\', ';
              }
            }
          if(false !== $db->Change('update userprofiles set ' . $newavatar . 'signature=\'' . addslashes(auText::BB2HTML(trim($_POST['signature']))) . '\', location=\'' . addslashes(htmlentities($_POST['location'], ENT_COMPAT, _CHARSET)) . '\', geekcode=\'' . addslashes(auText::EOL2br($_POST['geekcode'])) . '\', hackerkey=\'' . addslashes(htmlentities($_POST['hackerkey'], ENT_COMPAT, _CHARSET)) . '\' where uid=' . $u->uid, 'error saving profile'))
            $page->Info('profile successfully updated');
        }
        break;
      case 'display':
        if($_POST['submit'] == 'update') {
          if(isset($_POST['dst']))
            $now = strtotime($_POST['time'] . date(' T'));
          else
            $now = strtotime($_POST['time'] . ' GMT');
          $style = $u->style % 2 ? $u->style : $u->style - 1;
          if(isset($_POST['fullwidth']))
            $style++;
          $update = round(($now - time()) / 1800) * 1800;  // round to nearest half hour (1800 seconds)
          $update = 'update users set tzoffset=' . $update . ', flags=flags' . (isset($_POST['dst']) ? '|' . _FLAG_USERS_DST : '&' . (_FLAG_USERS ^ _FLAG_USERS_DST)) . ', style=' . $style . ' where uid=' . $u->uid;
          if(false !== $db->Change($update, 'error updating display preferences'))
            $page->Info('display preferences successfully updated.&nbsp; changes will take effect the next time a page is loaded');
        }
        break;
      case 'contact':
        $contact = 'select flags, website, jabber, icq, aim, steam, email from usercontact where uid=' . $u->uid;
        $contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found');
        if($_POST['submit'] == 'update') {
          if(strlen($_POST['website'] = trim($_POST['website'])) > 0 && strpos($_POST['website'], '://') === false)
            $_POST['website'] = 'http://' . $_POST['website'];
          if(false !== $db->Change('update usercontact set flags=flags' . ($_POST['showemail'] ? '|' . _FLAG_USERCONTACT_SHOWEMAIL : '&' . (_FLAG_USERCONTACT ^ _FLAG_USERCONTACT_SHOWEMAIL)) . ', website=\'' . addslashes(htmlspecialchars($_POST['website'])) . '\', jabber=\'' . addslashes(htmlspecialchars(trim($_POST['jabber']))) . '\', icq=\'' . addslashes(htmlspecialchars(trim($_POST['icq']))) . '\', aim=\'' . addslashes(htmlspecialchars(trim($_POST['aim']))) . '\', steam=\'' . addslashes(htmlentities($_POST['steam'], ENT_COMPAT, _CHARSET)) . '\' where uid=' . $u->uid, 'error updating contact information'))
            $page->Info('contact information successfully updated');
        }
        break;
      case 'notification':
        $contact = 'select flags, email from usercontact where uid=' . $u->uid;
        $contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found');
        if($_POST['submit'] == 'update' && is_numeric($_POST['messages']) && is_numeric($_POST['updates'])) {
          if(false !== $db->Change('update usercontact set flags=' . (+$contact->flags & _FLAG_USERCONTACT_SHOWEMAIL | $_POST['messages'] | $_POST['updates']) . ' where uid=' . $u->uid, 'error updating notification settings'))
            $page->Info('notification settings successfully updated');
        }
        break;
      case 'password':
        $contact = 'select flags, email from usercontact where uid=' . $u->uid;
        $contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found');
        if($_POST['submit'] == 'update') {
          if(!$user->CheckAnyPassword($_POST['oldpass']))
            $page->Error('old password incorrect or missing');
          else {
            $newpass = '';
            if(strlen($_POST['pass1'] = trim($_POST['pass1']))) {
              if(strlen($_POST['pass1']) < 4)
                $page->Error('your password must be at least 4 characters -- not changing password');
              elseif($_POST['pass1'] != trim($_POST['pass2']))
                $page->Error('passwords do not match!&nbsp; not changing password');
              elseif(false !== $db->Change('update users set pass=\'' . addslashes(auUser::EncryptPassword($_POST['pass1'])) . '\' where uid=' . $u->uid, 'error updating password'))
                $page->Info('password successfully changed');
            }
            if($_POST['email'] != $contact->email) {
              if(strpos($contact->email, '@'))
                @mail($contact->email, 'track7 e-mail change', 'a request has been received to change the e-mail address for \'' . $u->login ."'\r\n" . 'to \'' . $_POST['email'] . '\'', 'From: track7 <users@' . _HOST . '>');
              if(strpos($_POST['email'], '@'))
                @mail($_POST['email'], 'track7 e-mail change', 'your e-mail change request has been received!' . "\r\n" . 'if you did not see an error on the website, it is probably already changed for you.', 'From: track7 <users@' . _HOST . '>');
              if(false !== $db->Change('update usercontact set email=\'' . addslashes(htmlspecialchars(trim($_POST['email']))) . '\' where uid=' . $u->uid, 'error updating e-mail address'))
                $page->Info('e-mail address successfully updated');
            }
          }
        }
        break;
    }
?>
      <p><a href="/user/<?=$u->login; ?>/">view profile</a></p>
      <ul class="tabs">
        <li<?=$_GET['tab'] == 'profile' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=profile" title="edit avatar, signature, and geek code / hacker key">profile</a></li>
        <li<?=$_GET['tab'] == 'display' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=display" title="edit display settings">display</a></li>
        <li<?=$_GET['tab'] == 'contact' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=contact" title="edit contact information">contact</a></li>
        <li<?=$_GET['tab'] == 'notification' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=notification" title="edit notifications">notification</a></li>
        <li<?=$_GET['tab'] == 'password' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=password" title="change password and/or e-mail address">password</a></li>
      </ul>
      <div class="tabbed">
<?
    if($_GET['tab'] == 'password') {
?>
          <p class="info">in order to change your password or e-mail address, you must enter your current password</p>
<?
    }
    $prof = new auForm('editprofile', $querystring . 'tab=' . $_GET['tab']);
    switch($_GET['tab']) {
      case 'profile':
        if($profile->avatar)
          $prof->AddHTML('current avatar', '<img src="/user/avatar/' . $u->login . '.' . $profile->avatar . '" class="avatar" alt="" /> to keep your current avatar, leave the new avatar field blank.&nbsp; to change your avatar, select a jpeg or png image that does not exceed ' . AVATAR_SIZE . ' x ' . AVATAR_SIZE . ' pixels.');
        else
          $prof->AddHTML('current avatar', 'you do not currently have an avatar.&nbsp; to add an avatar, select a jpeg or png image that does not exceed ' . AVATAR_SIZE . ' x ' . AVATAR_SIZE . ' pixels.');
        $prof->AddField('avatar', 'new avatar', 'upload an avatar to display next to your forum posts', false, '', _AU_FORM_FIELD_FILE, 38);
        $prof->AddField('signature', 'signature', 'enter a signature to display below all of your forum posts', false, auText::HTML2BB($profile->signature), _AU_FORM_FIELD_BBCODE);
        $prof->AddField('location', 'location', 'enter your location', false, $profile->location, _AU_FORM_FIELD_NORMAL, 20, 32);
        $prof->AddField('geekcode', 'geek code', 'enter your geek code (www.geekcode.com)', false, auText::br2EOL($profile->geekcode), _AU_FORM_FIELD_MULTILINE, 60, 250);
        $prof->AddField('hackerkey', 'hacker key', 'enter your hacker key (www.hackerkey.com)', false, $profile->hackerkey, _AU_FORM_FIELD_NORMAL, 48, 250);
        break;
      case 'display':
        $prof->AddField('time', 'current time', 'enter the current time so track7 can display dates and times in your time zone', true, $user->tzdate('g:i a'), _AU_FORM_FIELD_NORMAL, 8, 20);
        $prof->AddField('dst', 'dst', 'adjust for daylight saving time', false, +$u->flags & _FLAG_USERS_DST, _AU_FORM_FIELD_CHECKBOX);
        $prof->Add(new auFormCheckbox('fullwidth', 'width', 'force pages to use the full width of the browser window', $u->style && $u->style % 2 == 0));
        break;
      case 'contact':
        $prof->AddHTML('e-mail address', $contact->email . ' (<a href="' . $querystring . 'tab=password">change</a>)');
        $prof->AddField('showemail', 'show e-mail', 'show e-mail address on track7 (beware spambots)', false, +$contact->flags & _FLAG_USERCONTACT_SHOWEMAIL, _AU_FORM_FIELD_CHECKBOX);
        $prof->AddField('website', 'website url', 'enter the url of your personal website if you have one', false, $contact->website, _AU_FORM_FIELD_NORMAL, 40, 55);
        $prof->AddField('jabber', 'jabber id', 'enter your jabber id if you use jabber', false, $contact->jabber, _AU_FORM_FIELD_NORMAL, 30, 64);
        $prof->AddField('icq', 'icq uin', 'enter your icq number if you use icq', false, $contact->icq, _AU_FORM_FIELD_INTEGER, 10, 10);
        $prof->AddField('aim', 'aim screen name', 'enter your screen name if you use aim', false, $contact->aim, _AU_FORM_FIELD_NORMAL, 10, 32);
        $prof->AddField('steam', 'steam id', 'enter your steam id if you use steam', false, $contact->steam, _AU_FORM_FIELD_NORMAL, 10, 32);
        break;
      case 'notification':
        $prof->AddHTML('e-mail address', $contact->email . ' (<a href="' . $querystring . 'tab=password">change</a>)');
        $prof->AddSelect('messages', 'unread messages', 'choose how track7 should notify you (by e-mail) of new messages', array(0 => 'never notify', _FLAG_USERCONTACT_NOTIFYMSGNOW => 'notify immediately'), +$contact->flags & _FLAG_USERCONTACT_NOTIFYMSGNOW);
// next line should replace previous line once i write the scripts for cron
//        $editprofile->select('messages', 'unread messages', 'choose how track7 should notify you of new messages', array(0, _FLAG_USERCONTACT_NOTIFYMSGWEEKLY, _FLAG_USERCONTACT_NOTIFYMSGDAILY, _FLAG_USERCONTACT_NOTIFYMSGNOW), array('never notify', 'notify weekly', 'notify daily', 'notify immediately'), +$_POST['messages']);
        $prof->AddSelect('updates', 'track7 updates', 'choose how track7 should notify you (by e-mail) of updates to the site', array(0 => 'never notify', _FLAG_USERCONTACT_NOTIFYNEWCONTENT => 'notify when new content is added', _FLAG_USERCONTACT_NOTIFYNEWCONTENT | _FLAG_USERCONTACT_NOTIFYNEWANYTHING => 'always notify'), +$contact->flags & (_FLAG_USERCONTACT_NOTIFYNEWCONTENT | _FLAG_USERCONTACT_NOTIFYNEWANYTHING));
        $prof->AddHTML('feeds', 'another way to keep up-to-date with track7 is to subscribe to one or more <a href="/feeds/">track7 feeds</a>.&nbsp; many newer e-mail programs and browsers are able to subscribe to feeds and automatically notify you when new content has been added to a feed.&nbsp; note that there is no feed for your personal messages.');
        break;
      case 'password':
        $prof->AddField('oldpass', 'current password', 'enter your current password if you are changing your password or e-mail address', true, '', _AU_FORM_FIELD_PASSWORD, 25);
        $prof->AddField('pass1', 'new password', 'enter a new password to change your password', false, '', _AU_FORM_FIELD_PASSWORD, 25);
        $prof->AddField('pass2', 'confirm password', 'enter your new password again to confirm', false, '', _AU_FORM_FIELD_PASSWORD, 25);
        $prof->AddField('email', 'e-mail address', 'enter your e-mail address', false, $contact->email, _AU_FORM_FIELD_NORMAL, 25, 55);
        break;
    }
    $prof->AddButtons('update', 'save changes to preferences');
    $prof->WriteHTML(true);
?>
      </div>
<?
  } else {
    $page->Start('edit profile');
    $page->Error('cannot edit your profile because you are not logged in!');
?>
      <p>
        if you have an account, please <a href="login.php">log in</a> and you
        will be able to edit your profile when you come back.&nbsp; if you don't
        have an account, you will need to <a href="register.php">register</a> so
        that you have a profile to edit!
      </p>
<?
  }
  $page->End();
?>
