<?php
require_once 'environment.php';

class Conversation {
	public Participant $With;
	public TimeTagData $Instant;
	public bool $Unread;
	public bool $Replied;

	public function __construct(CurrentUser $user, ?int $otherUserID, ?string $username, ?string $displayname, ?string $avatar, int $instant, bool $unread, bool $replied) {
		$this->With = new Participant($otherUserID, $username, $displayname, $avatar);
		require_once 'formatDate.php';
		$this->Instant = new TimeTagData($user, 'ago', $instant, FormatDate::Long);
		$this->Unread = $unread;
		$this->Replied = $replied;
	}

	public static function List(mysqli $db, CurrentUser $user): array {
		try {
			$select = $db->prepare('select u.id, u.username, u.displayname, u.avatar, unix_timestamp(m.instant), m.unread, m.recipient!=? from message as m left join user as u on (u.id=m.recipient or u.id=m.sender) and u.id!=? left join message as lm on (lm.recipient=u.id and lm.sender=? or lm.recipient=? and (lm.sender=u.id or lm.sender is null and u.id is null)) and lm.instant>m.instant where (m.recipient=? or m.sender=?) and lm.id is null order by m.instant desc');
			$select->bind_param('iiiiii', $user->ID, $user->ID, $user->ID, $user->ID, $user->ID, $user->ID);
			$select->execute();
			$select->bind_result($uid, $username, $displayname, $avatar, $instant, $unread, $replied);
			$results = [];
			while ($select->fetch())
				$results[] = new self($user, $uid, $username, $displayname, $avatar, $instant, $unread, $replied);
			return $results;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up conversations', $mse);
		}
	}
}

class Participant {
	public int $ID;
	public string $Name;
	public string $Avatar;
	public string $URL;

	public function __construct(?int $otherUserID, ?string $username, ?string $displayname, ?string $avatar) {
		require_once 'user.php';
		if ($this->ID = $otherUserID ?? 0) {
			$this->Name = $displayname ?: $username;
			$this->Avatar = $avatar ?: User::DefaultAvatar;
			$this->URL = "/user/$username/";
		} else {
			$this->Name = '(various unknown)';
			$this->Avatar = User::DefaultAvatar;
		}
	}
}

class Message {
	private const MaxMessageLoad = 6;

	public int $ID;
	public TimeTagData $Instant;
	public bool $Outgoing;
	public ?string $Name;
	public ?string $Contact;
	public string $HTML;
	public bool $Unread;

	private function __construct(CurrentUser $user, int $id, int $instant, bool $outgoing, ?string $name, ?string $contact, string $html, bool $unread) {
		$this->ID = $id;
		require_once 'formatDate.php';
		$this->Instant = new TimeTagData($user, FormatDate::LongHTML, $instant);
		$this->Outgoing = $outgoing;
		$this->Name = $name;
		$this->Contact = $contact;
		$this->HTML = $html;
		$this->Unread = $unread;
	}

	public static function List(mysqli $db, CurrentUser $user, int $withUserID, int $skip): MessageList {
		$limit = self::MaxMessageLoad + 1;
		try {
			$select = $db->prepare('select id, unix_timestamp(instant), not recipient=?, name, contact, html, unread from message where recipient=? and ifnull(sender,0)=? or recipient=? and sender=? order by instant desc limit ? offset ?');
			$select->bind_param('iiiiiii', $user->ID, $user->ID, $withUserID, $withUserID, $user->ID, $limit, $skip);
			$select->execute();
			$select->bind_result($id, $instant, $outgoing, $name, $contact, $html, $unread);
			$list = new MessageList();
			$idsToMarkRead = [];
			while ($select->fetch())
				if (count($list->Messages) >= self::MaxMessageLoad)
					$list->HasMore = true;
				else {
					array_unshift($list->Messages, new self($user, $id, $instant, $outgoing, $name, $contact, $html, $unread));
					if ($unread && !$outgoing)
						$idsToMarkRead[] = $id;
				}
			$select->close();

			if (count($idsToMarkRead)) {
				$update = $db->prepare('update message set unread=false where id=? and recipient=? limit 1');
				$update->bind_param('ii', $id, $user->ID);
				foreach ($idsToMarkRead as $id)
					$update->execute();
				$update->close();
			}
			return $list;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up messages', $mse);
		}
	}

	public static function Send(mysqli $db, CurrentUser $user, User $toUser, string $message, string $fromname, string $fromcontact): self {
		if ($user->ID == $toUser->ID)
			throw new DetailedException('cannot send message to yourself');
		require_once 'formatText.php';
		$html = FormatText::Markdown($message);
		return $user->IsLoggedIn() ? self::SendFromUser($db, $user, $toUser, $message, $html) : self::SendAnonymous($db, $user, $toUser, $message, $html, $fromname, $fromcontact);
	}

	private static function SendFromUser(mysqli $db, CurrentUser $user, User $toUser, string $markdown, string $html): self {
		try {
			$insert = $db->prepare('insert into message (recipient, sender, html, markdown) values (?, ?, ?, ?)');
			$insert->bind_param('iiss', $toUser->ID, $user->ID, $html, $markdown);
			$insert->execute();
			self::NotifyRecipient($db, $toUser, $user->DisplayName);
			return new self($user, $insert->insert_id, time(), true, null, null, $html, true);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error sending message', $mse);
		}
	}

	private static function SendAnonymous(mysqli $db, CurrentUser $user, User $toUser, string $markdown, string $html, string $fromname, string $fromcontact): self {
		if (!$fromname) {
			// avoid blank from name
			require_once 'user.php';
			$fromname = User::DefaultName;
		}
		require_once 'formatUrl.php';
		$fromcontact = FormatURL::ContactLink($fromcontact);
		try {
			$insert = $db->prepare('insert into message (recipient, name, contact, html, markdown) values (?, ?, nullif(?,\'\'), ?, ?)');
			$insert->bind_param('issss', $toUser->ID, $fromname, $fromcontact, $html, $markdown);
			$insert->execute();
			self::NotifyRecipient($db, $toUser, $fromname);
			return new self($user, $insert->insert_id, time(), true, $fromname, $fromcontact, $html, true);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error sending message', $mse);
		}
	}

	private static function NotifyRecipient(mysqli $db, User $toUser, $fromname): void {
		if (Responder::IsTestServer())
			return;  // don't actually send emails from test server
		try {
			$prefs = $toUser->GetNotificationSettings($db);
			if ($prefs->EmailNewMessage && $prefs->EmailAddress) {
				require_once 'formatUrl.php';
				$subject = "new message from $fromname";
				$body = 'visit ' . FormatUrl::FullUrl('/user/messages.php') . ' to read it and reply.' . "\r\n\r\n" . 'to change your e-mail settings, visit ' . FormatUrl::FullUrl('/user/settings.php#notification');
				$headers = ['X-Mailer: track7.org/php' . phpversion(), 'From: track7 messenger <messages@track7.org>'];
				@mail($prefs->EmailAddress, $subject, $body, $headers);
			}
		} catch (DetailedException $de) {
			if ($user->IsAdmin())  // absorb errors when not admin
				throw $de;
		}
	}
}

class MessageList {
	/**
	 * @var Message[] Group of messages loaded
	 */
	public array $Messages = [];
	/**
	 * Whether there are more messages to load
	 */
	public bool $HasMore = false;
}
