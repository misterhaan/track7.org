<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class UserTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('users');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckUserTable();
	}

	private static function CheckUserTable(): void {
		if (self::CheckTableExists('user')) {
?>
			<p>new <code>user</code> table exists.</p>
		<?php
			self::CheckUserRows();
		} else
			self::CreateTable('user');
	}

	private static function CheckUserRows(): void {
		$missing = self::$db->query('select 1 from users left join user on user.id=users.id where user.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyUsers();
		else {
		?>
			<p>all old users exist in new <code>user</code> table.</p>
		<?php
			self::CheckFriendTable();
		}
	}

	private static function CheckFriendTable(): void {
		if (self::CheckTableExists('friend')) {
		?>
			<p>new <code>friend</code> table exists.</p>
			<?php
			self::CheckFriendRows();
		} else
			self::CreateTable('friend');
	}

	private static function CheckFriendRows(): void {
		if (self::CheckTableExists('users_friends')) {
			$missing = self::$db->query('select 1 from users_friends left join friend on friend.fan=users_friends.fan and friend.friend=users_friends.friend where friend.fan is null limit 1');
			if ($missing->fetch_column())
				self::CopyFriends();
			else {
			?>
				<p>all old friends exist in new <code>friend</code> table.</p>
			<?php
				self::CheckOldFriendsTable();
			}
		} else {
			?>
			<p>old friends table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckOldFriendsTable(): void {
		if (self::CheckTableExists('users_friends'))
			self::DeleteOldFriendsTable();
		else {
		?>
			<p>old friends table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyUsers(): void {
		self::$db->real_query('insert into user select users.* from users left join user on user.id=users.id where user.id is null');
		?>
		<p>copied users into new <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyFriends(): void {
		self::$db->real_query('insert into friend select users_friends.* from users_friends left join friend on friend.fan=users_friends.fan and friend.friend=users_friends.friend where friend.fan is null');
	?>
		<p>copied friends into new <code>friend</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldFriendsTable(): void {
		self::$db->real_query('drop table users_friends');
	?>
		<p>deleted old friends table. refresh the page to take the next step.</p>
<?php
	}
}
new UserTransition();
