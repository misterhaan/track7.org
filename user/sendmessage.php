<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  // try to find someone to send this to, otherwise we will put together a list later
  if(isset($_POST['to']) && !isset($_GET['to']))
    $_GET['to'] = $_POST['to'];
  if(isset($_GET['to'])) {
    if(!is_numeric($_GET['to'])) {
      $to = 'select uid, login from users where uid>0 and login=\'' . addslashes($_GET['to']) . '\'';
      if($to = $db->GetRecord($to, 'error looking up user id of user to send to', 'could not find user id for specified login', true)) {
        $_GET['to'] = $to->uid;
        $to = $to->login;
        $page->start('send a message to ' . $to);
      } else {
        $page->start('send a message');
        unset($to);
      }
    } else {
      $to = 'select login from users where uid>0 and uid=' . $_GET['to'];
      if($to = $db->GetValue($to, 'error looking up login name for user to send to', ''))
        $page->start('send a message to ' . $to);
      else {
        $page->start('send a message');
        unset($to);
      }
    }
    if(isset($to) && is_numeric($_GET['reply'])) {
      $message = 'select m.fromuid, m.subject, m.message, u.login from usermessages as m left join users as u on u.uid=m.fromuid where m.id=' . $_GET['reply'];
      if($message = $db->GetRecord($message, 'error looking up information from message to reply to', 'message to reply to not found')) {
        if($message->fromuid != $_GET['to']) {
          $_GET['to'] = $message->fromuid;
          $to = $message->login;
        }
        $subject = $message->subject;
        if(strtolower(substr($subject, 0, 4)) != 're: ')
          $subject = 're: ' . $subject;
        $message = '[q=' . $to . ']' . auText::HTML2BB($message->message) . '[/q]';
      } else {
        unset($message);
        unset($_GET['reply']);
      }
    }
  } else
    $page->Start('send a message');
  $messageform = new auForm('sendmessage', '?to=' . $_GET['to']);
  $messageform->AddData('to', $_GET['to']);
  $messageform->AddText('to', $to);
  if($user->Valid)
    $messageform->AddText('from', $user->Name);
  else {
    $messageform->AddField('name', 'from name', 'enter your name so the recipient can know who you are', true, '', _AU_FORM_FIELD_NORMAL, 12, 45);
    $messageform->AddField('contact', 'from contact', 'enter your e-mail address or url so that the user can reply to you', false, '', _AU_FORM_FIELD_NORMAL, 20, 100);
  }
  $messageform->AddField('subject', 'subject', 'enter a subject so that the user knows what this message is about', false, $subject, _AU_FORM_FIELD_NORMAL, 50, 90);
  $messageform->AddField('message', 'message', 'enter the message you want to send (t7code is allowed)', true, $message, _AU_FORM_FIELD_BBCODE);
  $messageform->AddButtons(array('preview', 'send'), array('make sure everything is going to look right', 'send this message'));
  if(is_numeric($_GET['reply']))
    $messageform->AddData('reply', $_GET['reply']);
  elseif(is_numeric($_POST['reply']))
    $messageform->AddData('reply', $_POST['reply']);
  if($messageform->Submitted())
    if($messageform->CheckInput($user->Valid)) {
      if(strlen($_POST['message']) < 5)
        $page->Error('nothing to send!&nbsp; please enter at least 5 characters for your message.');
      if(strlen($_POST['subject']) < 1)
        $_POST['subject'] = '[no subject]';
      switch($messageform->Submitted()) {
        case 'preview':
?>
      <table class="columns" cellspacing="0">
        <tr><th>to</th><td><?=$to; ?></td></tr>
        <tr><th>from</th><td><?=$user->Valid ? '<a href="/user/' . $user->Name . '/">' . $user->Name : '<a href="' . htmlspecialchars(auText::FixLink($_POST['contact'])) . '">' . htmlspecialchars($_POST['name']); ?></a></td></tr>
        <tr><th>subject</th><td><?=htmlspecialchars($_POST['subject']); ?></td></tr>
      </table>
      <p>
        <?=auText::BB2HTML($_POST['message']); ?>
      </p>

      <hr class="minor" />

<?
          break;
        case 'send':
          if($user->Valid) {
            $ins = 'insert into usermessages (instant, touid, fromuid, subject, message) values (' . time() . ', ' . $_POST['to'] . ', ' . $user->ID . ', \'' . addslashes(htmlspecialchars($_POST['subject'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['message'])) . '\')';
            if(false !== $db->Put($ins, 'error saving message')) {
              $db->Change('update users set flags=flags|1 where uid=' . $_POST['to']);
              if(is_numeric($_POST['reply']))
                $db->Change('update usermessages set flags=flags|' . _FLAG_USERMESSAGES_REPLIED . ' where id=' . $_POST['reply']);
              $page->Info('message sent successfully!');
              $to = 'select email from usercontact where uid=' . $_POST['to'] . ' and flags & ' . _FLAG_USERCONTACT_NOTIFYMSGNOW;
              if($to = $db->GetValue($to, 'error seeing if the recipient wants to be e-mailed', ''))
                auSend::EMail('new message from ' . $user->Name, 'visit http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/message.php to read it.' . "\r\n\r\n" . 'to change your e-mail preferences, visit http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/editprofile.php', 'messages@' . _HOST, $to, 'track7 messenger');
              unset($to);
            }
          } elseif(strtolower(substr($_POST['message'], 0, 7)) != 'http://' && strtolower(substr($_POST['message'], 0, 4)) != '[url' && strtolower(substr($_POST['message'], 0, 5)) != '[link' && strtolower(substr($_POST['message'], 0, 3)) != '<a ') {
            if(strlen($_POST['name']) < 1)
              $_POST['name'] = 'anonymous';
            if(strlen($_POST['contact']) > 0)
              $_POST['contact'] = auText::FixLink($_POST['contact']);
            $ins = 'insert into usermessages (instant, touid, name, contact, subject, message) values (' . time() . ', ' . $_POST['to'] . ', \'' . addslashes(htmlspecialchars($_POST['name'])) . '\', \'' . addslashes($_POST['contact']) . '\', \'' . addslashes(htmlspecialchars($_POST['subject'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['message'])) . '\')';
            if(false !== $db->Put($ins, 'error saving message')) {
              $db->Change('update users set flags=(flags|1) where uid=' . $_POST['to']);
              $page->Info('message sent successfully!');
              $to = 'select email from usercontact where uid=' . $_POST['to'] . ' and flags&' . _FLAG_USERCONTACT_NOTIFYMSGNOW;
              if($to = $db->GetValue($to, 'error seeing if the recipient wants to be e-mailed', ''))
                auSend::EMail('new message from ' . $_POST['name'], 'visit http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/messages.php to read it.' . "\r\n\r\n" . 'to change your e-mail preferences, visit http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/editprofile.php', 'messages@' . _HOST, $to, 'track7 messenger');
              unset($to);
            }
          } else
            $page->Error('Uh-oh, your message looks like a spam!&nbsp; if you\'re not trying to spam, then don\'t start your message with a url.');
          break;
      }
    }

  if(isset($to)) {
    $messageform->WriteHTML($user->Valid);
  } else {
?>
      <p>
        enter a username below to send a message to a track7 user.
      </p>
      <form method="get" action="sendmessage.php"><div>
        <table class="columns" cellspacing="0">
          <tr class="first required"><th><label for="fldto" title="enter the login name of the user you would like to send a message to">user</label></th><td><input type="text" id="fldto" name="to" size="16" /></td></tr>
          <tr><td></td><td><input type="submit" value="go" title="bring up a form to send a message to this user" /></td></tr>
        </table>
      </div></form>
<?
  }

  $page->End();
?>
