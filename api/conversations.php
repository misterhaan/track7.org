<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for conversations api requests.
 * @author misterhaan
 */
class conversationsApi extends t7api {
	/**
	 * write out the documentation for the conversations api controller.  the page
	 * is already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
		<h2 id=getlist>get list</h2>
		<p>retrieves the list of conversations the logged-in user is involved in.</p>

		<h2 id=getmessages>get messages</h2>
		<p>retrieves messages from the requested conversation.</p>

		<h2 id=postsendMessage>post sendMessage</h2>
		<p>sends a message to a user.</p>
<?php
	}

	/**
	 * get conversations for the logged-in user.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if ($cs = $db->query('select c.id, c.thatuser, coalesce(nullif(u.displayname, \'\'), u.username, \'(various unknown)\') as displayname, u.username, coalesce(nullif(u.avatar, \'\'), \'/images/user.jpg\') as avatar, m.sent, m.author=\'' . +$user->ID . '\' as issender, m.hasread from users_conversations as c left join users as u on u.id=c.thatuser left join users_messages as m on m.id=c.latestmessage where c.thisuser=\'' . +$user->ID . '\' and c.latestmessage is not null order by m.sent desc')) {
				$ajax->Data->conversations = [];
				while ($c = $cs->fetch_object()) {
					$c->sent = t7format::TimeTag('ago', $c->sent, t7format::DATE_LONG);
					$ajax->Data->conversations[] = $c;
				}
			} else
				$ajax->Fail('error looking up conversations', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('tried to list conversations but nobody is logged in.');
	}

	/**
	 * get messages from a conversation.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function messagesAction($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn())
			if (isset($_GET['conversation']) && $_GET['conversation'] == ($conv = +$_GET['conversation'])) {
				$ms = 'select * from (select id, sent, author=\'' . +$user->ID . '\' as outgoing, name, contacturl, hasread, html from users_messages as m where m.conversation=\'' . $conv;
				if (isset($_GET['before']) && +$_GET['before'])
					$ms .= '\' and sent<\'' . +$_GET['before'];
				$ms .= '\' order by m.sent desc limit 4) as m order by sent';
				$ajax->Data->sql = $ms;
				if ($ms = $db->query($ms)) {
					$ajax->Data->messages = [];
					while ($m = $ms->fetch_object()) {
						if (!isset($ajax->Data->oldest))
							$ajax->Data->oldest = $m->sent;
						$m->sent = t7format::TimeTag(t7format::DATE_LONG, $m->sent);
						$ajax->Data->messages[] = $m;
					}
					$db->query('update users_messages set hasread=true where conversation=\'' . $conv . '\' and (author!=\'' . +$user->ID . '\' or author is null) and sent>=\'' . +$ajax->Data->oldest . '\'');
					if ($ajax->Data->hasmore = $db->query('select 1 from users_messages where conversation=\'' . $conv . '\' and sent<\'' . +$ajax->Data->oldest . '\' limit 1'))
						$ajax->Data->hasmore = $ajax->Data->hasmore->num_rows > 0;
					else
						$ajax->Data->hasmore = false;
				} else
					$ajax->Fail('error looking up messages', $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('conversation id must be specified.');
		else
			$ajax->Fail('tried to look up conversation messages but nobody is logged in.  this can happen if the messages page has been left open a long time.');
	}

	/**
	 * send a new message.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function sendMessageAction($ajax) {
		global $db, $user;
		if ($user->IsLoggedIn() || isset($_POST['fromname']) && isset($_POST['fromcontact']))
			if (isset($_POST['to']) && +isset($_POST['to']))
				if (isset($_POST['markdown']) && trim($_POST['markdown']))
					if ($to = $db->query('select id from users where id=\'' . +$_POST['to'] . '\' limit 1'))
						if ($to = $to->fetch_object()) {
							$to = +$to->id;
							$msg = new stdClass();
							$msg->sent = new stdClass();
							$timesent = +time();
							$msg->sent = t7format::TimeTag(t7format::DATE_LONG, $timesent);
							$msg->outgoing = 1;
							$msg->hasread = 0;
							$msg->name = '';
							$msg->contacturl = '';
							$msg->html = t7format::Markdown(trim($_POST['markdown']));
							if ($db->query('insert into users_messages (sent, conversation, ' . ($user->IsLoggedIn() ? 'author' : 'name, contacturl') . ', html, markdown) values (\'' . $timesent . '\', GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\'), \'' . ($user->IsLoggedIn() ? +$user->ID : (trim($_POST['fromname']) ? $db->escape_string(trim($_POST['fromname'])) : 'anonymous') . '\', \'' . $db->escape_string(t7format::Link(trim($_POST['fromcontact'])))) . '\', \'' . $db->escape_string($msg->html) . '\', \'' . $db->escape_string(trim($_POST['markdown'])) . '\')')) {
								$msg->id = $db->insert_id;
								$db->query('update users_conversations set latestmessage=\'' . +$msg->id . '\' where id=GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\') limit 2');
								if ($user->IsLoggedIn())
									$db->query('update users_messages set hasreplied=1 where conversation=GetConversationID(\'' . $to . '\', \'' . +$user->ID . '\') and author!=\'' . $user->ID . '\'');
								if ($email = $db->query('select emailnewmessage from settings where user=\'' . $to . '\' limit 1'))
									if ($email = $email->fetch_object())
										if ($email->emailnewmessage)
											if ($toemail = $db->query('select contact from contact where user=\'' . $to . '\' and type=\'email\' limit 1'))
												if ($toemail = $toemail->fetch_object())
													if ($toemail = $toemail->email)
														t7send::Email('new message from ' . $user->DisplayName, 'visit ' . t7format::FullUrl('/user/messages.php') . ' to read it and reply.' . "\r\n\r\n" . 'to change your e-mail settings, visit ' . t7format::FullUrl('/user/settings.php#notification'), 'messages@track7.org', $toemail, 'track7 messenger');
								$ajax->Data->message = $msg;
								$ajax->Data->timesent = $timesent;
							} else
								$ajax->Fail('error sending message:', $db->errno . ' ' . $db->error);
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
	}
}
conversationsApi::Respond();
