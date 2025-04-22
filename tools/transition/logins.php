<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class LoginTransition extends TransitionPage {
	public function __construct() {
		parent::__construct('logins');
	}

	protected static function MainContent(): void {
		parent::MainContent();
		self::CheckLoginTable();
	}

	private static function CheckLoginTable(): void {
		if (self::CheckTableExists('login')) {
?>
			<p>new <code>login</code> table exists.</p>
			<?php
			self::CheckLoginRows();
		} else
			self::CreateTable('login');
	}

	private static function CheckLoginRows(): void {
		$tablesExist = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'login_%\' or table_name=\'external_profiles\'');
		$tablesExist = $tablesExist->num_rows > 5;  // 5 login providers and external_profiles
		if ($tablesExist) {
			$missing = self::$db->query('select 1 from login_google as lg left join login as lgl on lgl.site=\'google\' and lgl.id=lg.sub where lgl.id is null union all select 1 from login_twitter as lt left join login as ltl on ltl.site=\'twitter\' and ltl.id=lt.user_id where ltl.id is null union all select 1 from login_github as lgh left join login as lghl on lghl.site=\'github\' and lghl.id=lgh.extid where lghl.id is null union all select 1 from login_deviantart as ld left join login as ldl on ldl.site=\'deviantart\' and ldl.id=ld.uuid where ldl.id is null union all select 1 from login_steam as ls left join login as lsl on lsl.site=\'steam\' and lsl.id=ls.steamID64 where lsl.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyLogins();
			else {
			?>
				<p>all old logins exist in new <code>login</code> table.</p>
			<?php
				self::CheckOldLoginTables();
			}
		} else {
			?>
			<p>old login tables no longer exist.</p>
		<?php
			self::CheckRememberTable();
		}
	}

	private static function CheckOldLoginTables(): void {
		$tablesExist = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'login_%\' or table_name=\'external_profiles\' limit 1');
		if ($tablesExist->num_rows > 0)
			self::DeleteOldLoginTables();
		else {
		?>
			<p>old login tables no longer exist.</p>
		<?php
			self::CheckRememberTable();
		}
	}

	private static function CheckRememberTable(): void {
		if (self::CheckTableExists('remember')) {
		?>
			<p>new <code>remember</code> table exists.</p>
			<?php
			self::CheckRememberRows();
		} else
			self::CreateTable('remember');
	}

	private static function CheckRememberRows(): void {
		if (self::CheckTableExists('login_remembered')) {
			$missing = self::$db->query('select 1 from login_remembered as lr left join remember as r on r.series=lr.series where lr.expires>unix_timestamp(now()) and r.series is null limit 1');
			if ($missing->num_rows)
				self::CopyRemember();
			else {
			?>
				<p>all old remember series exist in new <code>remember</code> table.</p>
			<?php
				self::DeleteOldRememberTable();
			}
		} else {
			?>
			<p>old remember table no longer exists.</p>
			<?php
			self::CheckPasswordColumn();
		}
	}

	private static function CheckPasswordColumn(): void {
		if (self::CheckTableExists('transition_login')) {
			$exists = self::$db->query('select 1 from information_schema.columns where table_schema=\'track7\' and table_name=\'user\' and column_name=\'passwordhash\' limit 1');
			if ($exists->num_rows) {
			?>
				<p>password column exists in <code>user</code> table.</p>
			<?php
				self::DeleteOldPasswordTable();
			} else
				self::AddPasswordColumn();
		} else {
			?>
			<p>old password table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyLogins(): void {
		self::$db->begin_transaction();
		self::$db->real_query('insert into login (site, id, user, name, url, avatar, linkavatar) select \'google\', lg.sub, lg.user, nullif(ep.name, \'\'), nullif(ep.url, \'\'), ep.avatar, ep.useavatar from login_google as lg left join external_profiles as ep on ep.id=lg.profile');
		self::$db->real_query('insert into login (site, id, user, name, url, avatar, linkavatar) select \'twitter\', lt.user_id, lt.user, nullif(ep.name, \'\'), nullif(ep.url, \'\'), ep.avatar, ep.useavatar from login_twitter as lt left join external_profiles as ep on ep.id=lt.profile');
		self::$db->real_query('insert into login (site, id, user, name, url, avatar, linkavatar) select \'github\', lg.extid, lg.user, nullif(ep.name, \'\'), nullif(ep.url, \'\'), ep.avatar, ep.useavatar from login_github as lg left join external_profiles as ep on ep.id=lg.profile');
		self::$db->real_query('insert into login (site, id, user, name, url, avatar, linkavatar) select \'deviantart\', ld.uuid, ld.user, nullif(ep.name, \'\'), nullif(ep.url, \'\'), ep.avatar, ep.useavatar from login_deviantart as ld left join external_profiles as ep on ep.id=ld.profile');
		self::$db->real_query('insert into login (site, id, user, name, url, avatar, linkavatar) select \'steam\', ls.steamID64, ls.user, nullif(ep.name, \'\'), nullif(ep.url, \'\'), ep.avatar, ep.useavatar from login_steam as ls left join external_profiles as ep on ep.id=ls.profile');
		self::$db->commit();
		?>
		<p>copied old logins to new <code>login</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyRemember(): void {
		self::$db->real_query('insert into remember (series, tokenhash, expires, user) select lr.series, lr.tokenhash, from_unixtime(lr.expires), lr.user from login_remembered as lr left join remember as r on r.series=lr.series where lr.expires>unix_timestamp(now()) and r.series is null');
	?>
		<p>copied old remember series to new <code>remember</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function AddPasswordColumn(): void {
		self::$db->begin_transaction();
		self::$db->real_query('alter table user add column passwordhash varchar(96) comment \'optional if user has external login\'');
		self::$db->real_query('update user as u left join transition_login as tl on tl.id=u.id set u.passwordhash=tl.pass');
		self::$db->commit();
	?>
		<p>added new <code>password</code> column to <code>user</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldLoginTables(): void {
		self::$db->real_query('drop table if exists login_google, login_twitter, login_github, login_deviantart, login_steam, external_profiles');
	?>
		<p>deleted old login tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldRememberTable(): void {
		self::$db->real_query('drop table if exists login_remembered');
	?>
		<p>deleted old remember table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldPasswordTable(): void {
		self::$db->real_query('drop table if exists transition_login');
	?>
		<p>deleted old password table. refresh the page to take the next step.</p>
<?php
	}
}
new LoginTransition();
