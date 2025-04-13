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
			self::CheckUserDateColumns();
		}
	}

	private static function CheckUserDateColumns(): void {
		$hasRegisteredColumn = self::$db->query('show columns from user like \'registered\'');
		$hasRegisteredColumn = $hasRegisteredColumn->num_rows > 0;
		$hasLastLoginColumn = self::$db->query('show columns from user like \'lastlogin\'');
		$hasLastLoginColumn = $hasLastLoginColumn->num_rows > 0;
		if ($hasRegisteredColumn && $hasLastLoginColumn) {
		?>
			<p>new <code>user</code> table has <code>registered</code> and <code>lastlogin</code> columns.</p>
			<?php
			self::CheckUserDateData();
		} else
			self::AddUserDateColumns(!$hasRegisteredColumn, !$hasLastLoginColumn);
	}

	private static function CheckUserDateData(): void {
		if (self::CheckTableExists('users_stats')) {
			$different = self::$db->query('select 1 from user as u left join users_stats as s on s.id=u.id where u.registered!=from_unixtime(s.registered) or u.lastlogin!=from_unixtime(s.lastlogin) limit 1');
			if ($different->num_rows > 0)
				self::CopyUserDateData();
			else {
			?>
				<p>all old user date data exists in new <code>user</code> table.</p>
			<?php
				self::CheckFriendTable();
			}
		} else {
			?>
			<p>old user stats table no longer exists.</p>
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
			self::CheckUserStatsView();
		}
	}

	private static function CheckUserStatsView(): void {
		if (self::CheckViewExists('userstats')) {
		?>
			<p>new <code>userstats</code> view exists.</p>
		<?php
			self::CheckUserRankingView();
		} else
			self::CreateView('userstats');
	}

	private static function CheckUserRankingView(): void {
		if (self::CheckViewExists('ranking')) {
		?>
			<p>new <code>ranking</code> view exists.</p>
		<?php
			self::CheckContactTable();
		} else
			self::CreateView('ranking');
	}

	private static function CheckContactTable(): void {
		if (self::CheckTableExists('contact')) {
		?>
			<p>new <code>contact</code> table exists.</p>
			<?php
			self::CheckEmailContactRows();
		} else
			self::CreateTable('contact');
	}

	private static function CheckEmailContactRows(): void {
		if (self::CheckTableExists('users_email')) {
			$missing = self::$db->query('select 1 from users_email left join contact on contact.user=users_email.id and contact.type=\'email\' and contact.contact=users_email.email where contact.user is null and users_email.email!=\'\' limit 1');
			if ($missing->num_rows)
				self::CopyEmailContactRows();
			else {
			?>
				<p>all old email contacts exist in new <code>contact</code> table.</p>
			<?php
				self::CheckProfileContactRows();
			}
		} else {
			?>
			<p>old email table no longer exists.</p>
			<?php
			self::CheckProfileContactRows();
		}
	}

	private static function CheckProfileContactRows(): void {
		if (self::CheckTableExists('users_profiles')) {
			$missing = self::$db->query('select 1 from users_profiles as p left join contact as wc on wc.user=p.id and wc.type=\'website\' and wc.contact=p.website left join contact as tc on tc.user=p.id and tc.type=\'twitter\' and tc.contact=p.twitter left join contact as fc on fc.user=p.id and fc.type=\'facebook\' and fc.contact=p.facebook left join contact as gc on gc.user=p.id and gc.type=\'github\' and gc.contact=p.github left join contact as dc on dc.user=p.id and dc.type=\'deviantart\' and dc.contact=p.deviantart left join contact as sc on sc.user=p.id and sc.type=\'steam\' and sc.contact=p.steam where wc.user is null and p.website!=\'\' or tc.user is null and p.twitter!=\'\' or fc.user is null and p.facebook!=\'\' or gc.user is null and p.github!=\'\' or dc.user is null and p.deviantart!=\'\' or sc.user is null and p.steam!=\'\' limit 1');
			if ($missing->num_rows)
				self::CopyProfileContactRows();
			else {
			?>
				<p>all old profiles exist in new <code>contact</code> table.</p>
			<?php
				self::CheckOldProfileTable();
			}
		} else {
			?>
			<p>old profile table no longer exists.</p>
		<?php
			self::CheckOldEmailTable();
		}
	}

	private static function CheckOldProfileTable(): void {
		if (self::CheckTableExists('users_profiles'))
			self::DeleteOldProfileTable();
		else {
		?>
			<p>old profile table no longer exists.</p>
		<?php
			self::CheckOldEmailTable();
		}
	}

	private static function CheckOldEmailTable(): void {
		if (self::CheckTableExists('users_email'))
			self::DeleteOldEmailTable();
		else {
		?>
			<p>old email table no longer exists.</p>
		<?php
			self::CheckOldUserStatsTable();
		}
	}

	private static function CheckOldUserStatsTable(): void {
		if (self::CheckTableExists('users_stats'))
			self::DeleteOldUserStatsTable();
		else {
		?>
			<p>old user stats table no longer exists.</p>
		<?php
			self::CheckOldFriendsTable();
		}
	}

	private static function CheckOldFriendsTable(): void {
		if (self::CheckTableExists('users_friends'))
			self::DeleteOldFriendsTable();
		else {
		?>
			<p>old friends table no longer exists.</p>
		<?php
			self::CheckOldTransitionTable();
		}
	}

	private static function CheckOldTransitionTable(): void {
		if (self::CheckTableExists('transition_users'))
			self::DeleteOldTransitionTable();
		else {
		?>
			<p>old transition users table no longer exists.</p>
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

	private static function AddUserDateColumns(bool $addRegistered, bool $addLastLogin): void {
		self::$db->begin_transaction();
		if ($addRegistered)
			self::$db->real_query('alter table user add column registered datetime not null default now() comment \'when the user first regisetered\', add key(registered)');
		if ($addLastLogin)
			self::$db->real_query('alter table user add column lastlogin datetime not null default now() comment \'when the user last logged in\', add key(lastlogin)');
		self::$db->commit();
	?>
		<p>added date columns to <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyUserDateData(): void {
		self::$db->real_query('update user as u left join users_stats as s on s.id=u.id set u.registered=from_unixtime(s.registered), u.lastlogin=from_unixtime(s.lastlogin)');
	?>
		<p>copied date information into new <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyFriends(): void {
		self::$db->real_query('insert into friend select users_friends.* from users_friends left join friend on friend.fan=users_friends.fan and friend.friend=users_friends.friend where friend.fan is null');
	?>
		<p>copied friends into new <code>friend</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyEmailContactRows(): void {
		self::$db->real_query('insert into contact select users_email.id, \'email\', users_email.email, users_email.vis_email from users_email left join contact on contact.user=users_email.id and contact.type=\'email\' and contact.contact=users_email.email where contact.user is null and users_email.email!=\'\'');
	?>
		<p>copied email contacts into new <code>contact</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyProfileContactRows(): void {
		self::$db->real_query('insert into contact select users_profiles.id, \'website\', users_profiles.website, users_profiles.vis_website from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'website\' and contact.contact=users_profiles.website where contact.user is null and users_profiles.website!=\'\'');
		self::$db->real_query('insert into contact select users_profiles.id, \'twitter\', users_profiles.twitter, users_profiles.vis_twitter from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'twitter\' and contact.contact=users_profiles.twitter where contact.user is null and users_profiles.twitter!=\'\'');
		self::$db->real_query('insert into contact select users_profiles.id, \'facebook\', users_profiles.facebook, users_profiles.vis_facebook from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'facebook\' and contact.contact=users_profiles.facebook where contact.user is null and users_profiles.facebook!=\'\'');
		self::$db->real_query('insert into contact select users_profiles.id, \'github\', users_profiles.github, users_profiles.vis_github from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'github\' and contact.contact=users_profiles.github where contact.user is null and users_profiles.github!=\'\'');
		self::$db->real_query('insert into contact select users_profiles.id, \'deviantart\', users_profiles.deviantart, users_profiles.vis_deviantart from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'deviantart\' and contact.contact=users_profiles.deviantart where contact.user is null and users_profiles.deviantart!=\'\'');
		self::$db->real_query('insert into contact select users_profiles.id, \'steam\', users_profiles.steam, users_profiles.vis_steam from users_profiles left join contact on contact.user=users_profiles.id and contact.type=\'steam\' and contact.contact=users_profiles.steam where contact.user is null and users_profiles.steam!=\'\'');
	?>
		<p>copied profile contacts into new <code>contact</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldProfileTable(): void {
		self::$db->real_query('drop table users_profiles');
	?>
		<p>deleted old profile table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldEmailTable(): void {
		self::$db->real_query('drop table users_email');
	?>
		<p>deleted old email table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldUserStatsTable(): void {
		self::$db->real_query('drop table users_stats');
	?>
		<p>deleted old user stats table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldFriendsTable(): void {
		self::$db->real_query('drop table users_friends');
	?>
		<p>deleted old friends table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTransitionTable(): void {
		self::$db->real_query('drop table transition_users');
	?>
		<p>deleted old transition users table. refresh the page to take the next step.</p>
<?php
	}
}
new UserTransition();
