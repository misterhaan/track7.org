<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'list':
        if($user->IsLoggedIn())
          if($cs = $db->query('select c.id, c.thatuser, coalesce(nullif(u.displayname, \'\'), u.username, \'(various unknown)\') as displayname, u.username, coalesce(nullif(u.avatar, \'\'), \'/images/user.jpg\') as avatar, m.sent, m.author=\'' . +$user->ID . '\' as issender, m.hasread from users_conversations as c left join users as u on u.id=c.thatuser left join users_messages as m on m.id=c.latestmessage where c.thisuser=\'' . +$user->ID . '\' and c.latestmessage is not null order by m.sent desc')) {
            $ajax->Data->conversations = [];
            while($c = $cs->fetch_object()) {
              $sent = new stdClass();
              $sent->datetime = gmdate('c', $c->sent);
              $sent->display = t7format::HowLongAgo($c->sent);
              $sent->tooltip = strtolower(t7format::LocalDate('g:i a \o\n l F jS Y', $c->sent));
              $c->sent = $sent;
              $ajax->Data->conversations[] = $c;
            }
          } else
            $ajax->Fail('error looking up conversations');
        else
          $ajax->Fail('tried to list conversations but nobody is logged in.');
        break;
      case 'messages':
        if($user->IsLoggedIn())
          if(isset($_GET['conversation']) && $_GET['conversation'] == +$_GET['conversation']) {
            $ms = 'select * from (select id, sent, author=\'' . +$user->ID . '\' as outgoing, name, contacturl, hasread, html from users_messages as m where m.conversation=\'' . +$_GET['conversation'];
            if(isset($_GET['before']) && +$_GET['before'])
              $ms .= '\' and sent<\'' . +$_GET['before'];
            $ms .= '\' order by m.sent desc limit 4) as m order by sent';
            $ajax->Data->query = $ms;
            if($ms = $db->query($ms)) {
              $ajax->Data->messages = [];
              $firstsent = false;
              while($m = $ms->fetch_object()) {
                if(!$firstsent)
                  $firstsent = $m->sent;
                $sent = new stdClass();
                $sent->timestamp = $m->sent;
                $sent->datetime = gmdate('c', $m->sent);
                $sent->display = strtolower(t7format::LocalDate('g:i a \o\n l F jS Y', $m->sent));
                $m->sent = $sent;
                $ajax->Data->messages[] = $m;
              }
              $db->query('update users_messages set hasread=true where conversation=\'' . +$_GET['conversation'] . '\' and (author!=\'' . +$user->ID . '\' or author is null) and sent>=\'' . +$firstsent . '\'');
              if($db->affected_rows)
                UpdateUnreadCount();
              if($ajax->Data->hasmore = $db->query('select 1 from users_messages where conversation=\'' . +$_GET['conversation'] . '\' and sent<\'' . +$firstsent . '\' limit 1'))
                $ajax->Data->hasmore = $ajax->Data->hasmore->num_rows > 0;
              else
                $ajax->Data->hasmore = false;
            } else
              $ajax->Fail('error looking up messages.');
          } else
            $ajax->Fail('conversation id must be specified.');
        else
          $ajax->Fail('tried to look up conversation messages but nobody is logged in.  this can happen if the messages page has been left open a long time.');
        break;
      case 'send':
        if($user->IsLoggedIn() || isset($_POST['fromname']) && isset($_POST['fromcontact']))
          if(isset($_POST['to']) && +isset($_POST['to']))
            if(isset($_POST['markdown']) && trim($_POST['markdown']))
              if($to = $db->query('select id from users where id=\'' . +$_POST['to'] . '\' limit 1'))
                if($to = $to->fetch_object()) {
                  $to = +$to->id;
                  $msg = new stdClass();
                  $msg->sent = new stdClass();
                  $msg->sent->timestamp = +time();
                  $msg->sent->datetime = gmdate('c', $msg->sent->timestamp);
                  $msg->sent->display = strtolower(t7format::LocalDate('g:i a \o\n l F jS Y', $msg->sent->timestamp));
                  $msg->outgoing = 1;
                  $msg->hasread = 0;
                  $msg->name = '';
                  $msg->contacturl = '';
                  $msg->html = t7format::Markdown(trim($_POST['markdown']));
                  if($db->query('insert into users_messages (sent, conversation, ' . ($user->IsLoggedIn() ? 'author' : 'name, contacturl') . ', html, markdown) values (\'' . $msg->sent->timestamp . '\', GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\'), \'' . ($user->IsLoggedIn() ? +$user->ID : (trim($_POST['fromname']) ? $db->escape_string(trim($_POST['fromname'])) : 'anonymous') . '\', \'' . $db->escape_string(t7format::Link(trim($_POST['fromcontact'])))) . '\', \'' . $db->escape_string($msg->html) . '\', \'' . $db->escape_string(trim($_POST['markdown'])) . '\')')) {
                    $msg->id = $db->insert_id;
                    $db->query('update users_conversations set latestmessage=\'' . +$msg->id . '\' where id=GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\') limit 2');
                    UpdateUnreadCount($to);
                    if($user->IsLoggedIn())
                      $db->query('update users_messages set hasreplied=1 where conversation=GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\') and author!=\'' . $user->ID . '\'');
                    if($email = $db->query('select emailnewmsg from users_settings where id=\'' . $to . '\' limit 1'))
                      if($email = $email->fetch_object())
                        if($email->emailnewmsg)
                          if($toemail = $db->query('select email from users_email where id=\'' . $to . '\' limit 1'))
                            if($toemail = $toemail->fetch_object())
                              if($toemail = $toemail->email)
                                t7send::Email('new message from ' . $user->DisplayName, 'visit http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . ' to read it and reply.' . "\r\n\r\n" . 'to change your e-mail settings, visit http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/settings.php#notification', 'messages@track7.org', $toemail, 'track7 messenger');
                    $ajax->Data->message = $msg;
                  } else
                  $ajax->Fail('error sending message.');
                } else
                  $ajax->Fail('recipient not found.');
              else
                $ajax->Fail('error verifying recipient.');
            else
              $ajax->Fail('message text missing or blank.  we can’t send it if you didn’t write it.');
          else
            $ajax->Fail('recipient id missing or non-numeric.');
        else
          $ajax->Fail('nobody’s logged in and we didn’t ask your name.  this can happen if the messages page has been left open a long time, and should probably be fixed my signing back in using a new tab so you don’t lose the message you just wrote.');
        break;
      default:
        $ajax->Fail('unknown command.  supported commands are:  list, messages, send.');
        break;
    }
    $ajax->Send();
    die;
  }

  $html = new t7html(['ko' => true]);
  $html->Open('messages');
?>
      <h1>track7 messages</h1>

<?php
  if($user->IsLoggedIn()) {
?>
      <div id=messages>
        <div id=conversations>
          <form id=sendtouser>
            <label title="search for a user to send a message to"><span class=field>
              <input type=search placeholder="find a person" autocomplete=off data-bind="textInput: usermatch">
            </span></label>
          </form>
          <ol class=usersuggest data-bind="visible: usermatch().length >= 3">
            <li class=message data-bind="visible: findingusers">finding people...</li>
            <!-- ko foreach: matchingusers -->
            <li class=suggesteduser data-bind="click: $parent.GetConversation, css: {highlight: id == $parent.cursor().id}">
              <img class=avatar alt="" data-bind="attr: {src: avatar}">
              <span data-bind="text: displayname || username"></span>
              <img src="/images/friend.png" alt="*" data-bind="if: isfriend, attr: {title: (displayname || username) + ' is your friend'}">
            </li>
            <!-- /ko -->
            <li class=message data-bind="visible: !findingusers() && matchingusers().length < 1">nobody here by that name</li>
          </ol>
          <ol class=conversations data-bind="foreach: conversations">
            <li data-bind="css: {selected:  $parent.selected().id == id}">
              <header data-bind="css: {read: hasread != 0, outgoing: issender, incoming: !issender}, click: $parent.Select"><img class=avatar data-bind="attr: {src: avatar}"><span data-bind="text: displayname"></span><time data-bind="text: sent.display, attr: {datetime: sent.datetime, title: sent.tooltip}"></time></header>
<?php ShowMessages(); ?>
            </li>
          </ol>
        </div>

        <div id=conversationpane data-bind="if: selected">
          <!-- ko with: selected -->
<?php ShowMessages(true); ?>
          <!-- /ko -->
        </div>
      </div>
<?php
  } else {
?>
      <p>
        hello, mysterious stranger!  while we welcome your messages to track7
        users, we suggest you either sign in or leave a contact e-mail or url
        where you can receive a response.
      </p>
      <form id=sendmessage data-bind="submit: Send">
        <label title="user who will receive this message">
          <span class=label>to:</span>
          <span class=field>
            <input id=usermatch type=search autocomplete=off data-bind="textInput: usermatch, visible: !chosenuser()">
            <span data-bind="visible: chosenuser">
              <img class=avatar data-bind="attr: {src: chosenuser().avatar}">
              <a data-bind="attr: {href: '/user/' + chosenuser().username + '/'}, text: chosenuser().displayname || chosenuser().username"></a>
              <a class="action del" data-bind="click: Clear, attr: {title: 'remove ' + chosenuser().displayname + ' and choose someone else'}"></a>
            </span>
          </span>
        </label>
        <ol class=usersuggest data-bind="visible: usermatch().length >= 3">
          <li class=message data-bind="visible: findingusers">finding people...</li>
          <!-- ko foreach: matchingusers -->
          <li class=suggesteduser data-bind="click: $parent.Select, css: {highlight: id == $parent.cursor().id}">
            <img class=avatar alt="" data-bind="attr: {src: avatar}">
            <span data-bind="text: displayname || username"></span>
          </li>
          <!-- /ko -->
          <li class=message data-bind="visible: !findingusers() && matchingusers().length < 1">nobody here by that name</li>
        </ol>
        <label title="your name">
          <span class=label>from:</span>
          <span class=field><input id=fromname maxlength=48></span>
        </label>
        <label title="e-mail address or url where replies can be sent (optional)">
          <span class=label>contact:</span>
          <span class=field><input id=fromcontact maxlength=255></span>
        </label>
        <label class=multiline title="message text you would like to send (markdown allowed)">
          <span class=label>message:</span>
        <span class=field><textarea id=markdown></textarea></span>
        </label>
        <button data-bind="attr: {disabled: !chosenuser()}">send</button>
        <!-- ko foreach: sentmessages -->
        <h2 data-bind="text: 'message sent ' + sent.display"></h2>
        <div data-bind="html: html"></div>
        <!-- /ko -->
      </form>
<?php
  }
  $html->Close();

  function ShowMessages($pane = false) {
    global $user;
    // TODO:  add preview button or live preview feature
?>
              <ol class=messages data-bind="<?php if(!$pane) echo 'visible: $parent.selected().id == id'; ?>">
                <li class=loading data-bind="visible: loading">
                  loading messages...
                </li>
                <li class=showmore data-bind="visible: hasmore">
                  <a class="action get" data-bind="click: $parent.LoadMessages" href="#!LoadMessages">load older messages</a>
                </li>
                <!-- ko foreach: messages -->
                <li data-bind="css: {outgoing: outgoing == 1, incoming: outgoing != 1}, attr: {id: '<?php if($pane) echo 'p'; ?>m' + id}">
                  <div class=userinfo>
                    <div class=username data-bind="visible: outgoing != 1 && !$parent.username && !contacturl, text: name"></div>
                    <div class=username data-bind="visible: outgoing != 1 && !$parent.username && contacturl"><a data-bind="text: name, attr: {href: contacturl}"></a></div>
                    <div class=username data-bind="visible: outgoing == 0 && $parent.username"><a data-bind="text: $parent.displayname, attr: {href: '/user/' + $parent.username + '/'}"></a></div>
                    <div class=username data-bind="visible: outgoing == 1"><a href="/user/<?php echo $user->Username; ?>/"><?php echo $user->DisplayName; ?></a></div>
                    <img class=avatar data-bind="visible: outgoing != 1 && $parent.avatar, attr: {src: $parent.avatar}">
                    <img class=avatar data-bind="visible: outgoing == 1" src="<?php echo $user->Avatar; ?>">
                  </div>
                  <div class=message>
                    <header>sent <time data-bind="text: sent.display, attr: {datetime: sent.datetime}"></time></header>
                    <div class=content data-bind="html: html"></div>
                  </div>
                </li>
                <!-- /ko -->
                <li class="outgoing reply" data-bind="visible: username">
                  <div class=userinfo>
                    <div class=username><a href="/user/<?php echo $user->Username; ?>/"><?php echo $user->DisplayName; ?></a></div>
                    <img class=avatar src="<?php echo $user->Avatar; ?>">
                  </div>
                  <div class=message>
                    <form class=reply data-bind="submit: function() {$parent.Reply($data);}">
                      <label class=multiline title="message text you would like to send (markdown allowed)">
                        <span class=label>reply:</span>
                        <span class=field><textarea data-bind="value: response, attr: {placeholder: 'write to ' + displayname}"></textarea></span>
                      </label>
                      <button>send</button>
                    </form>
                  </div>
                </li>
              </ol>
<?php
  }

  function UpdateUnreadCount($uid = false) {
    global $user, $db;
    if(!$uid)
      $uid = $user->ID;
    $db->query('update users_settings as us set unreadmsgs=(select count(1) from users_conversations as uc left join users_messages as um on um.id=uc.latestmessage where uc.thisuser=\'' . +$uid . '\' and um.author!=\'' . +$uid . '\' and um.hasread=0);');
  }
?>
