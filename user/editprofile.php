<?
/*----------------------------------------------------------------------------*\
 | purpose:  edit profile of currently logged-in user.                        |
 |                                                                            |
\*----------------------------------------------------------------------------*/
  define('AVATAR_SIZE', 64);
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

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
          if(false !== $db->Change('update userprofiles set ' . $newavatar . 'signature=\'' . addslashes(str_replace('</p><p>', '<br /><br />', auText::BB2HTML(trim($_POST['signature'])))) . '\', location=\'' . addslashes(htmlspecialchars($_POST['location'], ENT_COMPAT, _CHARSET)) . '\', geekcode=\'' . addslashes(auText::EOL2br($_POST['geekcode'])) . '\', hackerkey=\'' . addslashes(htmlspecialchars($_POST['hackerkey'], ENT_COMPAT, _CHARSET)) . '\' where uid=' . $u->uid, 'error saving profile'))
            $page->Info('profile successfully updated');
        }
        break;
      case 'computers':
        if($_POST['submit'] == 'update') {
          if($_POST['computer']) {
            $chk = 'select id from computers where uid=\'' . $u->uid . '\' and id=\'' . addslashes($_POST['computer']) . '\'';
            if(false !== $db->GetValue($chk, 'error verifying this computer belongs to you', 'this computer doesn’t exist or doesn’t belong to you', true)) {
              $update = 'update computers set name=\''
               . addslashes(htmlspecialchars(trim($_POST['name']), ENT_COMPAT, _CHARSET)) . '\', class=\''
               . addslashes(htmlspecialchars(trim($_POST['class']), ENT_COMPAT, _CHARSET)) . '\', purpose=\''
               . addslashes(htmlspecialchars(trim($_POST['purpose']), ENT_COMPAT, _CHARSET)) . '\', processor=\''
               . addslashes(htmlspecialchars(trim($_POST['processor']), ENT_COMPAT, _CHARSET)) . '\', mainboard=\''
               . addslashes(htmlspecialchars(trim($_POST['mainboard']), ENT_COMPAT, _CHARSET)) . '\', ram=\''
               . addslashes(auText::EOL2br(trim($_POST['ram']))) . '\', video=\''
               . addslashes(auText::EOL2br(trim($_POST['video']))) . '\', audio=\''
               . addslashes(htmlspecialchars(trim($_POST['audio']), ENT_COMPAT, _CHARSET)) . '\', tuner=\''
               . addslashes(auText::EOL2br(trim($_POST['tuner']))) . '\', network=\''
               . addslashes(auText::EOL2br(trim($_POST['network']))) . '\', hdd=\''
               . addslashes(auText::EOL2br(trim($_POST['hdd']))) . '\', optical=\''
               . addslashes(auText::EOL2br(trim($_POST['optical']))) . '\', reader=\''
               . addslashes(htmlspecialchars(trim($_POST['reader']), ENT_COMPAT, _CHARSET)) . '\', keyboard=\''
               . addslashes(htmlspecialchars(trim($_POST['keyboard']), ENT_COMPAT, _CHARSET)) . '\', mouse=\''
               . addslashes(htmlspecialchars(trim($_POST['mouse']), ENT_COMPAT, _CHARSET)) . '\', joystick=\''
               . addslashes(auText::EOL2br(trim($_POST['joystick']))) . '\', monitor=\''
               . addslashes(auText::EOL2br(trim($_POST['monitor']))) . '\', printer=\''
               . addslashes(htmlspecialchars(trim($_POST['printer']), ENT_COMPAT, _CHARSET)) . '\', scanner=\''
               . addslashes(htmlspecialchars(trim($_POST['scanner']), ENT_COMPAT, _CHARSET)) . '\', os=\''
               . addslashes(auText::EOL2br(trim($_POST['os']))) . '\', other=\''
               . addslashes(auText::EOL2br(trim($_POST['other']))) . '\' where id=\'' . addslashes($_POST['computer']) . '\'';
              if(false !== $db->Change($update, 'error updating computer', 'no changes made'))
                $page->Info('computer updates saved');
            }
          } else {
            $ins = 'insert into computers (uid, name, class, purpose, processor, mainboard, ram, video, audio, tuner, network, hdd, optical, reader, keyboard, mouse, joystick, monitor, printer, scanner, os, other) values (' . $u->uid . ', \''
             . addslashes(htmlspecialchars(trim($_POST['name']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['class']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['purpose']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['processor']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['mainboard']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['ram']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['video']))) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['audio']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['tuner']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['network']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['hdd']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['optical']))) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['reader']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['keyboard']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['mouse']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['joystick']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['monitor']))) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['printer']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(htmlspecialchars(trim($_POST['scanner']), ENT_COMPAT, _CHARSET)) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['os']))) . '\', \''
             . addslashes(auText::EOL2br(trim($_POST['other']))) . '\')';
            if(false !== $db->Put($ins, 'error saving computer'))
              $page->Info('new computer saved');
          }
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
        $contact = 'select flags, website, jabber, icq, aim, steam, twitter, spore, email from usercontact where uid=' . $u->uid;
        $contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found');
        if($_POST['submit'] == 'update') {
          if(strlen($_POST['website'] = trim($_POST['website'])) > 0 && strpos($_POST['website'], '://') === false)
            $_POST['website'] = 'http://' . $_POST['website'];
          if(false !== $db->Change('update usercontact set flags=flags' . ($_POST['showemail'] ? '|' . _FLAG_USERCONTACT_SHOWEMAIL : '&' . (_FLAG_USERCONTACT ^ _FLAG_USERCONTACT_SHOWEMAIL)) . ', website=\'' . addslashes(htmlspecialchars($_POST['website'])) . '\', jabber=\'' . addslashes(htmlspecialchars(trim($_POST['jabber']))) . '\', icq=\'' . addslashes(htmlspecialchars(trim($_POST['icq']))) . '\', aim=\'' . addslashes(htmlspecialchars(trim($_POST['aim']))) . '\', steam=\'' . addslashes(htmlspecialchars($_POST['steam'], ENT_COMPAT, _CHARSET)) . '\', twitter=\'' . addslashes(htmlspecialchars($_POST['twitter'], ENT_COMPAT, _CHARSET)) . '\', spore=\'' . addslashes(htmlspecialchars($_POST['spore'], ENT_COMPAT, _CHARSET)) . '\' where uid=' . $u->uid, 'error updating contact information'))
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
                auSend::EMail('track7 e-mail change', 'a request has been received to change the e-mail address for \'' . $u->login . '\' to \'' . $_POST['email'] . '\'', 'users@' . _HOST, $contact->email, 'track7');
              if(strpos($_POST['email'], '@'))
                auSend::EMail('track7 e-mail change', 'your e-mail change request has been received!' . "\r\n" . 'if you did not see an error on the website, it is probably already changed for you.', 'users@' . _HOST, $_POST['email'], 'track7');
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
        <li<?=$_GET['tab'] == 'computers' ? ' class="active"' : ''; ?>><a href="<?=$querystring; ?>tab=computers" title="add / edit / remove computer specs">computers</a></li>
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
      case 'computers':
        $editcomputer = false;
        $computers = 'select id, name, class, purpose, processor, mainboard, ram, video, audio, tuner, network, hdd, optical, reader, keyboard, mouse, joystick, monitor, printer, scanner, os, other from computers where uid=' . $u->uid;
        if($computers = $db->Get($computers, 'error looking up user’s computers', '')) {
?>
      <ul class=actions id=editcomputers>
<?
          while($computer = $computers->NextRecord()) {
            if($_GET['computer'] == $computer->id || $_POST['computer'] == $computer->id || $_GET['id'] != 'new' && $computers->NumRecords() == 1)
              $editcomputer = $computer;
?>
        <li class="<?=$computer->class; ?>"><a href="<?=$querystring; ?>tab=computers&amp;computer=<?=$computer->id; ?>"><?=$computer->name; ?></a><?=($computer->purpose ? ' (' . $computer->purpose . ')' : ''); ?></li>
<?
          }
          if($editcomputer && $editcomputer->name) {  // don't allow adding a computer if the first doesn't have a name
?>
        <li class=new><a href="<?=$querystring; ?>tab=computers&amp;computer=new">add computer</a></li>
<?
          }
?>
      </ul>
<?
        }
        if($editcomputer)
          $prof->Add(new auFormData('computer', $editcomputer->id));
        $cset = $prof->Add(new auFormFieldSet($editcomputer ? 'editing computer ' . $editcomputer->name : 'add computer'));
        $cset->Add(new auFormString('name', 'name', 'name of this computer (can be blank if you only list one)', $editcomputer->name || $_GET['computer'] == 'new', html_entity_decode($editcomputer->name, ENT_COMPAT, _CHARSET), 20, 64));
        $cset->Add(new auFormSelect('class', 'class', 'hardware class of this computer (not what it’s used for)', true, auFormSelect::ArrayIndex(array('server', 'workstation', 'laptop', 'netbook', 'tablet')), $editcomputer ? $editcomputer->class : 'workstation'));
        $cset->Add(new auFormString('purpose', 'purpose', 'what this computer is used for', false, html_entity_decode($editcomputer->purpose, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormString('processor', 'processor', 'what processor is in this computer', true, html_entity_decode($editcomputer->processor, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormString('mainboard', 'mainboard', 'what mainboard (motherboard) is in this computer', false, html_entity_decode($editcomputer->mainboard, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormMultiString('ram', 'ram', 'the ram (memory) in this computer', true, auText::br2EOL($editcomputer->ram), false, 40, 255));
        $cset->Add(new auFormMultiString('video', 'video', 'the video card (or onboard graphics controller) in this computer', false, auText::br2EOL($editcomputer->video), false, 40, 255));
        $cset->Add(new auFormString('audio', 'audio', 'the sound card (or onboard sound controller) in this computer', false, html_entity_decode($editcomputer->audio, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormMultiString('tuner', 'tuner', 'the tv tuner card in this computer', false, auText::br2EOL($editcomputer->tuner), false, 40, 255));
        $cset->Add(new auFormMultiString('network', 'network', 'the network controllers in this computer (wired and wireless)', false, auText::br2EOL($editcomputer->network), false, 40, 255));
        $cset->Add(new auFormMultiString('hdd', 'hdd', 'hard disk (or solid state) drives in this computer', false, auText::br2EOL($editcomputer->hdd), false, 40, 255));
        $cset->Add(new auFormMultiString('optical', 'optical', 'cd / dvd / bluray drives in this computer', false, auText::br2EOL($editcomputer->optical), false, 40, 255));
        $cset->Add(new auFormString('reader', 'reader', 'memory card reader used with this computer', false, html_entity_decode($editcomputer->reader, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormString('keyboard', 'keyboard', 'keyboard used with this computer', false, html_entity_decode($editcomputer->keyboard, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormString('mouse', 'mouse', 'mouse or other pointing device used with this computer', false, html_entity_decode($editcomputer->mouse, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormMultiString('joystick', 'joystick', 'joystick or gamepad used with this computer', false, auText::br2EOL($editcomputer->joystick), false, 40, 128));
        $cset->Add(new auFormMultiString('monitor', 'monitor', 'monitors used with this computer', false, auText::br2EOL($editcomputer->monitor), false, 40, 255));
        $cset->Add(new auFormString('printer', 'printer', 'printer used with this computer', false, html_entity_decode($editcomputer->printer, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormString('scanner', 'scanner', 'scanner used with this computer', false, html_entity_decode($editcomputer->scanner, ENT_COMPAT, _CHARSET), 40, 128));
        $cset->Add(new auFormMultiString('os', 'os', 'operating systems installed on this computer', false, auText::br2EOL($editcomputer->os), false, 40, 128));
        $cset->Add(new auFormMultiString('other', 'other', 'any other parts of this computer', false, auText::br2EOL($editcomputer->other), false, 40, 255));
        break;
      case 'display':
        $prof->AddField('time', 'current time', 'enter the current time so track7 can display dates and times in your time zone', true, $user->tzdate('g:i a'), _AU_FORM_FIELD_NORMAL, 8, 20);
        $prof->AddField('dst', 'dst', 'adjust for daylight saving time', false, +$u->flags & _FLAG_USERS_DST, _AU_FORM_FIELD_CHECKBOX);
        $prof->Add(new auFormCheckbox('fullwidth', 'width', 'force pages to use the full width of the browser window', $u->style && $u->style % 2 == 0));
        break;
      case 'contact':
        $prof->Add(new auFormHTML('e-mail address', $contact->email . ' (<a href="' . $querystring . 'tab=password">change</a>)'));
        $prof->Add(new auFormCheckbox('showemail', 'show e-mail', 'show e-mail address on track7 (beware spambots)', false, +$contact->flags & _FLAG_USERCONTACT_SHOWEMAIL));
        $prof->Add(new auFormString('website', 'website url', 'enter the url of your personal website if you have one', false, $contact->website, 40, 55, _AU_FORM_STRING_URL));
        $prof->Add(new auFormString('jabber', 'jabber id', 'enter your jabber id if you use jabber', false, $contact->jabber, 30, 64, _AU_FORM_STRING_EMAIL));
        $prof->Add(new auFormInteger('icq', 'icq uin', 'enter your icq number if you use icq', false, $contact->icq, 10, 10));
        $prof->Add(new auFormString('aim', 'aim screen name', 'enter your screen name if you use aim', false, $contact->aim, 10, 32));
        $prof->Add(new auFormString('twitter', 'twitter username', 'enter your username if you use twitter', false, $contact->twitter, 10, 16));
        $prof->Add(new auFormString('steam', 'steam id', 'enter your steam id if you use steam', false, $contact->steam, 10, 32));
        $prof->Add(new auFormString('spore', 'spore screen name', 'enter your screen name if you play spore', false, $contact->spore, 10, 32));
        break;
      case 'notification':
        $prof->AddHTML('e-mail address', $contact->email . ' (<a href="' . $querystring . 'tab=password">change</a>)');
        $prof->AddSelect('messages', 'unread messages', 'choose how track7 should notify you (by e-mail) of new messages', array(0 => 'never notify', _FLAG_USERCONTACT_NOTIFYMSGNOW => 'notify immediately'), +$contact->flags & _FLAG_USERCONTACT_NOTIFYMSGNOW);
// next line should replace previous line once i write the scripts for cron
//        $editprofile->select('messages', 'unread messages', 'choose how track7 should notify you of new messages', array(0, _FLAG_USERCONTACT_NOTIFYMSGWEEKLY, _FLAG_USERCONTACT_NOTIFYMSGDAILY, _FLAG_USERCONTACT_NOTIFYMSGNOW), array('never notify', 'notify weekly', 'notify daily', 'notify immediately'), +$_POST['messages']);
        $prof->AddSelect('updates', 'track7 updates', 'choose how track7 should notify you (by e-mail) of updates to the site', array(0 => 'never notify', _FLAG_USERCONTACT_NOTIFYNEWCONTENT => 'notify when new content is added', _FLAG_USERCONTACT_NOTIFYNEWCONTENT | _FLAG_USERCONTACT_NOTIFYNEWANYTHING => 'always notify'), +$contact->flags & (_FLAG_USERCONTACT_NOTIFYNEWCONTENT | _FLAG_USERCONTACT_NOTIFYNEWANYTHING));
        $prof->AddHTML('feeds', 'another way to keep up-to-date with track7 is to subscribe to one or more <a href="/feeds/">track7 feeds</a>, or follow <a href="http://twitter.com/track7feed">@track7feed</a> on twitter.&nbsp; many newer e-mail programs and browsers are able to subscribe to feeds and automatically notify you when new content has been added to a feed.&nbsp; note that there is no feed for your personal messages.');
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
        if you have an account, please <a id="messageloginlink" href="login.php">log in</a>
        and you will be able to edit your profile when you come back.&nbsp; if
        you don't have an account, you will need to <a href="register.php">register</a>
        so that you have a profile to edit!
      </p>
<?
  }
  $page->End();
?>
