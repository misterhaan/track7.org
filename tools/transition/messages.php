<?php
  define('TR_MESSAGES', 4);
  define('STEP_CHECKUSERS', 1);
  define('STEP_COPYNOTIFICATION', 2);
  define('STEP_COPYMESSAGES', 3);
  define('STEP_LASTMESSAGE', 4);
  define('STEP_COUNTUNREAD', 5);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('user messages');
?>
      <h1>user message migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'checkusers':
        if($us = $db->query('select * from (select tu.id, u.uid, u.login, (select count(1) from track7_t7data.usermessages where touid=u.uid) as received, (select count(1) from track7_t7data.usermessages where fromuid=u.uid) as sent from track7_t7data.users as u left join transition_users as tu on tu.olduid=u.uid) as t where (received>0 or sent>0) and id is null'))
          if($us->num_rows) {
?>
      <p>
        the following users sent or received messages and haven’t been migrated:
      </p>
      <ul>
<?php
            while($u = $us->fetch_object()) {
?>
        <li><a href="/user/<?php echo $u->login; ?>/"><?php echo $u->login; ?></a> (<?php echo $u->sent; ?> sent, <?php echo $u->received; ?> received) <a href="users.php?migrate=<?php echo $u->uid; ?>">migrate</a></li>
<?php
            }
?>
      </ul>
      <p>
        visit <a href="users.php">user migration</a> and migrate these users
        before continuing.
      </p>
<?php
          } else {
            if(!$db->real_query('update transition_status set stepnum=' . STEP_CHECKUSERS . ', status=\'messaging users migrated\' where id=' . TR_MESSAGES . ' and stepnum<' . STEP_CHECKUSERS)) {
?>
      <p class=error>error updating message migration status:  <?php echo $db->error; ?></p>
<?php
            }
          }
        else {
?>
      <p class=error>error checking for unmigrated users who sent or received messages</p>
<?php
        }
        break;
      case 'copynotification':
        if($db->real_query('update users_settings as s set s.emailnewmsg=(select c.flags&24=24 as emailnewmsg from transition_users as t left join track7_t7data.usercontact as c on c.uid=t.olduid where t.id=s.id limit 1)'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYNOTIFICATION . ', status=\'notification settings copied\' where id=\'' . TR_MESSAGES . '\' and stepnum<' . STEP_COPYNOTIFICATION);
        else {
?>
      <p class=error>error copying notification settings:  <?php echo $db->error; ?></p>
<?php
        }
        break;
      case 'copymessages':
        if($db->real_query('insert into users_messages (sent, conversation, author, name, contacturl, subject, html, hasread, hasreplied) select m.instant, GetConversationID(tu.id, fu.id) as conversation, fu.id as author, m.name, m.contact, m.subject, concat(\'<p>\', replace(replace(replace(m.message, \'&rsquo;\', \'’\'), \'&mdash;\', \'—\'), \'&nbsp;\', \' \'), \'</p>\') as html, m.flags & 1 as hasread, (m.flags & 2) div 2 as hasreplied from track7_t7data.usermessages as m left join transition_users as tu on tu.olduid=m.touid left join transition_users as fu on fu.olduid=m.fromuid order by m.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYMESSAGES . ', status=\'messages copied\' where id=\'' . TR_MESSAGES . '\' and stepnum<' . STEP_COPYMESSAGES);
        else {
?>
      <p class=error>error copying messages:  <?php echo $db->error; ?></p>
<?php
        }
        break;
      case 'lastmessage':
        if($db->real_query('update users_conversations as c join (select m1.id, m1.conversation from users_messages as m1 left join users_messages as m2 on m1.conversation=m2.conversation and m1.sent<m2.sent where m2.conversation is null) as m on c.id=m.conversation set c.latestmessage=m.id'))
          $db->real_query('update transition_status set stepnum=' . STEP_LASTMESSAGE . ', status=\'last messages linked to conversations\' where id=\'' . TR_MESSAGES . '\' and stepnum<' . STEP_LASTMESSAGE);
        else {
?>
      <p class=error>error linking last message for each conversation:  <?php echo $db->error; ?></p>
<?php
        }
        break;
      case 'countunread':
        if($db->real_query('update users_settings as us set us.unreadmsgs=(select count(1) from users_conversations as uc left join users_messages as um on um.id=uc.latestmessage where uc.thisuser=us.id and um.author!=us.id and um.hasread=0)'))
          $db->real_query('update transition_status set stepnum=' . STEP_COUNTUNREAD . ', status=\'conversations with unread messages counted\' where id=\'' . TR_MESSAGES . '\' and stepnum<' . STEP_COUNTUNREAD);
        else {
?>
      <p class=error>error counting conversations with unread messages:  <?php echo $db->error; ?></p>
<?php
        }
        break;
    }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_MESSAGES))
    $status = $status->fetch_object();

?>
      <h2>messagers</h2>
<?php
  if($status->stepnum < STEP_CHECKUSERS) {
?>
      <p>
        before user messages can be migrated, all users who have sent or
        received a message must migrate.
      </p>
      <nav class=actions><a href="?dostep=checkusers">check user migration status</a></nav>
<?php
  } else {
?>
      <p>
        all messagers have been migrated.
      </p>

      <h2>notification settings</h2>
<?php
    if($status->stepnum < STEP_COPYNOTIFICATION) {
?>
      <p>
        now that all the messaging users are here, copy their new message
        notification setting.  make sure the <code>emailnewmsg</code> column has
        been added to <code>users_settings</code> first so the query doesn’t
        error out.
      </p>
      <nav class=actions><a href="?dostep=copynotification">copy notification settings</a></nav>
<?php
    } else {
?>
      <p>
        new message notification settings have been copied.
      </p>

      <h2>messages</h2>
<?php
      if($status->stepnum < STEP_COPYMESSAGES) {
?>
      <p>
        ready to copy messages to the new database.
      </p>
      <nav class=actions><a href="?dostep=copymessages">copy messages</a></nav>
<?php
      } else {
?>
      <p>
        all messages have been migrated.
      </p>

      <h2>conversations</h2>
<?php
       if($status->stepnum < STEP_LASTMESSAGE) {
?>
      <p>
        ready to link latest message for each conversation.
      </p>
      <nav class=actions><a href="?dostep=lastmessage">link latest messages</a></nav>
<?php
        } else {
?>
      <p>
        all conversations link to latest message in the conversation.
      </p>

      <h2>unread messages</h2>
<?php
          if($status->stepnum < STEP_COUNTUNREAD) {
?>
      <p>
        ready to count conversations with unread messages and update each user
        so they’ll know how many unread messages they have.
      </p>
      <nav class=action><a href="?dostep=countunread">count conversations with unread messages</a></nav>
<?php
          } else {
?>
      <p>
        conversations with unread messages have been counted.
      </p>
<?php
          }
        }
      }
    }
  }
  $html->Close();
?>
