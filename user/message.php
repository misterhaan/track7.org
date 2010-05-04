<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(isset($_GET['view']) && is_numeric($_GET['view'])) {
    $message = 'select m.instant, m.subject, m.message, u.login, m.name, m.contact from usermessages as m left join users as u on u.uid=m.fromuid where m.id=' . $_GET['view'] . ' and m.touid=' . $user->ID;
    if($message = $db->GetRecord($message, 'unable to open message', 'unable to open message ' . $_GET['view'] . ':&nbsp; it has either been deleted or was not addressed to you.&nbsp; a list of your messages should appear below.')) {
      $page->Start($message->subject, 'messages');
      // mark the message as read
      $db->Change('update usermessages set flags=flags|1 where id=' . $_GET['view']);
?>
      <table class="columns" cellspacing="0">
        <tr><th>from</th><td><a href="<?=$message->login ? $message->login . '/">' . $message->login : $message->contact . '">' . $message->name; ?></a></td></tr>
        <tr><th>sent</th><td><?=strtolower($user->tzdate('D, M j, Y \a\t g:i a', $message->instant)); ?></td></tr>
        <tr><th>subject</th><td><span class="response"><?=$message->subject; ?></span></td></tr>
      </table>
      <p>
        <?=$message->message; ?>
      </p>

<?
      if($message->login) {
?>
      <ul>
        <li><a href="sendmessage.php?to=<?=$message->login; ?>&amp;reply=<?=$_GET['view']; ?>">reply</a></li>
      </ul>

<?
      }
      $page->End();
      die;
    }
  }

  $page->Start('messages');
  if(!$user->Valid) {
?>
      <p>
        you are not logged in.&nbsp; if you don't have an account, then nobody
        can send you messages here, nor can you track messages that you've sent
        to other track7 users.&nbsp; you will need to either
        <a href="register.php">register</a> or
        <a href="login.php">log in</a> before this page will be of any use to
        you.
      </p>

<?
  } else {
    // clear the new messages flag from the user
    $db->Change('update users set flags=flags&254 where uid=' . $user->ID);
    // see if they want to go deleting something
    if(isset($_POST['submit']) && $_POST['submit'] == 'delete selected' && count($_POST['delmsg'])) {
      $del = '';
      foreach($_POST['delmsg'] as $id => $on)
        $del .= ' or id=' . $id;
      $delq = 'delete from usermessages where (touid=' . $user->ID . ' or fromuid=' . $user->ID . ' and (flags&1=0)) and (' . substr($del, 4) . ')';
      if(false !== $db->Change($delq, 'error attempting to delete messages')) {
        $del = 'select id from usermessages where ' . substr($del, 4);
        if($del = $db->Get($del, 'error checking for messages that couldn\'t be deleted', '')) {
          $msg = '';
          while($id = $del->NextRecord())
            $msg .= ', ' . $id->id;
          $page->Error('could not delete message(s) ' . substr($msg, 2) . ' -- you may only delete messages that were sent to you, or messages you sent that have not yet been read.');
        }
      }
    }
?>
      <h2>incoming</h2>
<?
    $messages = 'select m.id, m.instant, u.login, m.name, m.contact, m.subject, m.flags from usermessages as m left join users as u on m.fromuid=u.uid where m.touid=' . $user->ID . ' order by (m.flags & 1), instant desc';
    if($messages = $db->Get($messages, 'error getting a list of your messages'))
      if($messages->NumRecords()) {
?>
      <p>
        the following messages have been sent to you by other visitors.&nbsp;
        please delete any that you no longer need in order to keep the message
        system from slowing down.
      </p>
      <form method="post" action="<?=$_SERVER['PHP_SELF']; ?>"><div>
        <table class="text" cellspacing="0">
          <thead><tr><th></th><th>subject</th><th>received</th><th>from</th></tr></thead>
          <tfoot><tr><td colspan="4"><input type="submit" name="submit" value="delete selected" /></td></tr></tfoot>
          <tbody>
<?
        while($message = $messages->NextRecord())
          echo '            <tr><td><input type="checkbox" name="delmsg[' . $message->id . ']" /></td><td><a href="' . $_SERVER['PHP_SELF'] . '?view=' . $message->id . '" title="read this message" class="msg' . ($message->flags & _FLAG_USERMESSAGES_REPLIED ? 'replied' : ($message->flags & _FLAG_USERMESSAGES_READ ? 'read' : 'unread')) . '">' . $message->subject . '</a></td><td>' . $user->tzdate('Y-m-d h:i a', $message->instant) . '</td><td>' . ($message->login ? '<a href="' . $message->login . '/" title="view ' . $message->login . '\'s profile">' . $message->login . '</a>' : (strlen($message->contact) > 0 ? '<a href="' . $message->contact . '">' . $message->name . '</a>' : '<span class="response">' . $message->name . '</span>')) .  '</td></tr>' . "\n";
?>
          </tbody>
        </table>
      </div></form>
<?
      } else {
?>
      <p>
        you currently have no messages in the system.&nbsp; either nobody has
        anything to say to you or you are doing a good job of keeping your
        incoming messages clean!
      </p>
<?
      }
?>
      <h2>outgoing</h2>
<?
    $messages = 'select m.id, m.instant, u.login, m.subject from usermessages as m left join users as u on m.touid=u.uid where m.flags & 1 = 0 and m.fromuid=' . $user->ID . ' order by instant desc';
    if($messages = $db->Get($messages, 'error getting a list of messages you sent'))
      if($messages->NumRecords()) {
?>
      <p>
        you have sent the following messages to other users.&nbsp; they will
        show here (and allow you to delete them) until whoever you sent them to
        reads them.
      </p>
      <form method="post" action="<?=$_SERVER['PHP_SELF']; ?>"><div>
        <table class="text" cellspacing="0">
          <thead><tr><th></th><th>subject</th><th>sent</th><th>to</th></tr></thead>
          <tfoot><tr><td colspan="4"><input type="submit" name="submit" value="delete selected" /></td></tr></tfoot>
          <tbody>
<?
        while($message = $messages->NextRecord())
          echo '            <tr><td><input type="checkbox" name="delmsg[' . $message->id . ']" /></td><td>' . $message->subject . '</td><td>' . $user->tzdate('Y-m-d h:i a', $message->instant) . '</td><td><a href="' . $message->login . '/" title="view ' . $message->login . '\'s profile">' . $message->login . '</a></td></tr>' . "\n";
?>
          </tbody>
        </table>
      </div></form>
<?
      } else {
?>
      <p>
        you currently have no messages waiting to be read.&nbsp; either you
        don't say much or people are quick to read whatever it is you do say!
      </p>
<?
      }
  }
?>
      <h2>send a message</h2>
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
  $page->End();
?>
