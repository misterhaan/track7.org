<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class MessagesTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('messages');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckTransitionTable();
	}

	private static function CheckTransitionTable(): void {
		if (self::CheckTableExists('transition_status'))
			self::DeleteTransitionTable();
		else
			self::CheckMessageTable();
	}

	private static function CheckMessageTable(): void {
		if (self::CheckTableExists('message')) {
?>
			<p>new <code>message</code> table exists.</p>
			<?php
			self::CheckMessageRows();
		} else
			self::CreateTable('message');
	}

	private static function CheckMessageRows(): void {
		if (self::CheckTableExists('users_messages')) {
			if (self::CheckTableExists('users_conversations')) {
				$missing = self::$db->query('select 1 from users_messages as um left join message as m on m.instant=from_unixtime(um.sent) where m.id is null limit 1');
				if ($missing->fetch_column())
					self::CopyMessages();
				else {
			?>
					<p>all old messages exist in new <code>message</code> table.</p>
				<?php
					self::CheckOldConversationFunction();
				}
			} else {
				?>
				<p>old conversations table no longer exists.</p>
			<?php
				self::CheckOldMessagesTable();
			}
		} else {
			?>
			<p>old messages table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckOldConversationFunction(): void {
		$exists = self::$db->prepare('select 1 from information_schema.routines where routine_schema=\'track7\' and routine_name=\'GetConversationID\' limit 1');
		$exists->execute();
		$routineExists = $exists->fetch();
		$exists->close();
		if ($routineExists)
			self::DeleteOldConversationFunction();
		else {
		?>
			<p>old conversation id function no longer exists.</p>
		<?php
			self::CheckOldConversationsTable();
		}
	}

	private static function CheckOldConversationsTable(): void {
		if (self::CheckTableExists('users_conversations'))
			self::DeleteOldConversationsTable();
		else {
		?>
			<p>old conversations table no longer exists.</p>
		<?php
			self::CheckOldMessagesTable();
		}
	}

	private static function CheckOldMessagesTable(): void {
		if (self::CheckTableExists('users_messages'))
			self::DeleteOldMessagesTable();
		else {
		?>
			<p>old messages table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function DeleteTransitionTable(): void {
		self::$db->real_query('drop table transition_status');
		?>
		<p>deleted old transition table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyMessages(): void {
		self::$db->real_query('insert into message (instant, recipient, unread, sender, name, contact, html, markdown) select from_unixtime(um.sent), c.thisuser, if(um.hasread, false, true), um.author, nullif(um.name,\'\'), nullif(um.contacturl,\'\'), um.html, um.markdown from users_messages as um left join users_conversations as c on c.id=um.conversation and (c.thatuser=um.author or c.thatuser=0 and um.author is null) left join message as m on m.instant=from_unixtime(um.sent) where m.id is null order by um.sent');
	?>
		<p>copied old messages to new <code>message</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldConversationFunction(): void {
		self::$db->real_query('drop function GetConversationID');
	?>
		<p>deleted old conversation id function. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldConversationsTable(): void {
		self::$db->real_query('drop table users_conversations');
	?>
		<p>deleted old conversations table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldMessagesTable(): void {
		self::$db->real_query('drop table users_messages');
	?>
		<p>deleted old messages table. refresh the page to take the next step.</p>
<?php
	}
}
new MessagesTransition();
