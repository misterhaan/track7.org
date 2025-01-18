<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class CodeTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('code', 7, 'code', 'software', 'download free software with source code', 'coded');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		self::CheckCalcPostRows();
	}

	private static function CheckCalcPostRows(): void {
		if (self::CheckTableExists('code_calc_progs')) {
			$missing = self::$db->query('select 1 from code_calc_progs left join post on post.subsite=\'code\' and post.url=concat(\'/code/calc/#\', code_calc_progs.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyCalcToPost();
			else {
?>
				<p>all old calculator programs exist in new <code>post</code> table.</p>
			<?php
				self::CheckCalcTable();
			}
		} else {
			?>
			<p>old calculator programs table no longer exists.</p>
			<?php
			self::CheckGameworldPostRows();
		}
	}

	private static function CheckGameworldPostRows(): void {
		if (self::CheckTableExists('code_game_worlds')) {
			$missing = self::$db->query('select 1 from code_game_worlds left join post on post.subsite=\'code\' and post.url=concat(\'/code/games/#\', code_game_worlds.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyGameworldsToPost();
			else {
			?>
				<p>all old game worlds exist in new <code>post</code> table.</p>
			<?php
				self::CheckGameworldTable();
			}
		} else {
			?>
			<p>old game worlds table no longer exists.</p>
			<?php
			self::CheckApplicationPostRows();
		}
	}

	private static function CheckApplicationPostRows(): void {
		if (self::CheckTableExists('code_vs_applications')) {
			$missing = self::$db->query('select 1 from code_vs_applications left join post on post.subsite=\'code\' and post.url=concat(\'/code/vs/\', code_vs_applications.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyApplicationsToPost();
			else {
			?>
				<p>all old applications exist in new <code>post</code> table.</p>
			<?php
				self::CheckApplicationTable();
			}
		} else {
			?>
			<p>old applications table no longer exists.</p>
			<?php
			self::CheckScriptPostRows();
		}
	}

	private static function CheckScriptPostRows(): void {
		if (self::CheckTableExists('code_web_scripts')) {
			$missing = self::$db->query('select 1 from code_web_scripts left join post on post.subsite=\'code\' and post.url=concat(\'/code/web/\', code_web_scripts.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyScriptsToPost();
			else {
			?>
				<p>all old scripts exist in new <code>post</code> table.</p>
			<?php
				self::CheckScriptTable();
			}
		} else {
			?>
			<p>old web scripts table no longer exists.</p>
		<?php
			self::CheckContributions();
		}
	}

	private static function CheckCalcTable(): void {
		if (self::CheckTableExists('calcprog')) {
		?>
			<p>new <code>calcprog</code> table exists.</p>
		<?php
			self::CheckCalcRows();
		} else
			self::CreateTable('calcprog');
	}

	private static function CheckGameworldTable(): void {
		if (self::CheckTableExists('gameworld')) {
		?>
			<p>new <code>gameworld</code> table exists.</p>
		<?php
			self::CheckGameworldRows();
		} else
			self::CreateTable('gameworld');
	}

	private static function CheckApplicationTable(): void {
		if (self::CheckTableExists('application')) {
		?>
			<p>new <code>application</code> table exists.</p>
		<?php
			self::CheckApplicationRows();
		} else
			self::CreateTable('application');
	}

	private static function CheckReleaseTable(): void {
		if (self::CheckTableExists('release')) {
		?>
			<p>new <code>release</code> table exists.</p>
		<?php
			self::CheckLatestApplicationView();
		} else
			self::CreateTable('release');
	}

	private static function CheckLatestApplicationView(): void {
		if (self::CheckViewExists('latestapplication')) {
		?>
			<p>new <code>latestapplication</code> view exists.</p>
		<?php
			self::CheckReleaseRows();
		} else
			self::CreateView('latestapplication');
	}

	private static function CheckScriptTable(): void {
		if (self::CheckTableExists('script')) {
		?>
			<p>new <code>script</code> table exists.</p>
		<?php
			self::CheckScriptRows();
		} else
			self::CreateTable('script');
	}

	private static function CheckCalcRows(): void {
		$missing = self::$db->query('select 1 from code_calc_progs left join calcprog on calcprog.id=code_calc_progs.url where calcprog.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyToCalcProg();
		else {
		?>
			<p>all old calculator programs exist in new <code>calcprog</code> table.</p>
		<?php
			self::CheckGameworldPostRows();
		}
	}

	private static function CheckGameworldRows(): void {
		$missing = self::$db->query('select 1 from code_game_worlds left join gameworld on gameworld.id=code_game_worlds.url where gameworld.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyToGameworlds();
		else {
		?>
			<p>all old gameworlds exist in new <code>gameworld</code> table.</p>
		<?php
			self::CheckApplicationPostRows();
		}
	}

	private static function CheckApplicationRows(): void {
		$missing = self::$db->query('select 1 from code_vs_applications left join application on application.id=code_vs_applications.url where application.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyToApplications();
		else {
		?>
			<p>all old applications exist in new <code>application</code> table.</p>
		<?php
			self::CheckReleaseTable();
		}
	}

	private static function CheckReleaseRows(): void {
		$missing = self::$db->query('select 1 from code_vs_releases as r left join code_vs_applications as a on a.id=r.application left join `release` on release.application=a.url and release.major=r.major and release.minor=r.minor and release.revision=r.revision where release.application is null limit 1');
		if ($missing->fetch_column())
			self::CopyToReleases();
		else {
		?>
			<p>all old application releases exist in new <code>release</code> table.</p>
		<?php
			self::CheckScriptTable();
		}
	}

	private static function CheckScriptRows(): void {
		$missing = self::$db->query('select 1 from code_web_scripts left join script on script.id=code_web_scripts.url where script.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyToScripts();
		else {
		?>
			<p>all old web scripts exist in new <code>script</code> table.</p>
			<?php
			self::CheckTagTable();
		}
	}

	protected static function CheckTagRows(): void {
		self::CheckPostTagTable();
	}

	protected static function CheckPostTagRows(): void {
		self::CheckTagUsageView();
	}

	protected static function CheckCommentRows(): void {
		self::CheckApplicationCommentRows();
	}

	private static function CheckApplicationCommentRows(): void {
		if (self::CheckTableExists('code_vs_comments')) {
			$missing = self::$db->query('select 1 from code_vs_comments as ac left join code_vs_applications as oa on oa.id=ac.application left join application as a on a.id=oa.url left join comment as c on c.post=a.post and c.instant=from_unixtime(ac.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyApplicationComments();
			else {
			?>
				<p>all old application comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckApplicationCommentTriggers();
			}
		} else {
			?>
			<p>old application comment table no longer exists.</p>
			<?php
			self::CheckScriptCommentRows();
		}
	}

	private static function CheckScriptCommentRows(): void {
		if (self::CheckTableExists('code_web_comments')) {
			$missing = self::$db->query('select 1 from code_web_comments as sc left join code_web_scripts as os on os.id=sc.script left join script as s on s.id=os.url left join comment as c on c.post=s.post and c.instant=from_unixtime(sc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyScriptComments();
			else {
			?>
				<p>all old web script comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckScriptCommentTriggers();
			}
		} else {
			?>
			<p>old web script comment table no longer exists.</p>
		<?php
			self::CheckOldCalc();
		}
	}

	private static function CheckApplicationCommentTriggers(): void {
		if (self::CheckTriggersExist('code_vs_comments'))
			self::DeleteApplicationCommentTriggers();
		else {
		?>
			<p>old application comment triggers no longer exist.</p>
		<?php
			self::CheckApplicationTrigger();
		}
	}

	private static function CheckApplicationTrigger(): void {
		if (self::CheckTriggersExist('code_vs_applications'))
			self::DeleteApplicationTrigger();
		else {
		?>
			<p>old application trigger no longer exists.</p>
		<?php
			self::CheckReleaseTrigger();
		}
	}

	private static function CheckReleaseTrigger(): void {
		if (self::CheckTriggersExist('code_vs_releases'))
			self::DeleteReleaseTrigger();
		else {
		?>
			<p>old application release trigger no longer exists.</p>
		<?php
			self::CheckApplicationProcedures();
		}
	}

	private static function CheckApplicationProcedures(): void {
		$exists = self::$db->query('select 1 from information_schema.routines where routine_schema=\'track7\' and routine_name like \'ListApplication%\' limit 1');
		if ($exists->fetch_column())
			self::DeleteApplicationProcedures();
		else {
		?>
			<p>old application procedures no longer exist.</p>
		<?php
			self::CheckScriptCommentTriggers();
		}
	}

	private static function CheckScriptCommentTriggers(): void {
		if (self::CheckTriggersExist('code_web_comments'))
			self::DeleteScriptCommentTriggers();
		else {
		?>
			<p>old web script comment triggers no longer exist.</p>
		<?php
			self::CheckScriptTriggers();
		}
	}

	private static function CheckScriptTriggers(): void {
		if (self::CheckTriggersExist('code_web_scripts'))
			self::DeleteScriptTriggers();
		else {
		?>
			<p>old web script triggers no longer exist.</p>
		<?php
			self::CheckOldCalc();
		}
	}

	private static function CheckOldCalc(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'code_calc_%\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldCalc();
		else {
		?>
			<p>old calculator program tables no longer exist.</p>
		<?php
			self::CheckOldGameworlds();
		}
	}

	private static function CheckOldGameworlds(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'code_game_%\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldGameworlds();
		else {
		?>
			<p>old game world tables no longer exist.</p>
		<?php
			self::CheckOldApplications();
		}
	}

	private static function CheckOldApplications(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'code_vs_%\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldApplications();
		else {
		?>
			<p>old application tables no longer exist.</p>
		<?php
			self::CheckOldScripts();
		}
	}

	private static function CheckOldScripts(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name like \'code_web_%\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldScripts();
		else {
		?>
			<p>old web script tables no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		if (self::CheckTableExists('contributions')) {
			$exists = self::$db->query('select 1 from contributions where srctbl in (\'code_vs_releases\', \'code_web_scripts\', \'code_web_comments\') limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>code contributions no longer exist.</p>
			<?php
				self::Done();
			}
		} else {
			?>
			<p>old contributions table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyCalcToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(c.released), c.name, \'code\', concat(\'/code/calc/#\', c.url), 1, c.deschtml, true from code_calc_progs as c left join post on post.subsite=\'code\' and post.url=concat(\'/code/calc/#\', c.url) where post.id is null');
		?>
		<p>copied calculator programs into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGameworldsToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(w.released), w.name, \'code\', concat(\'/code/games/#\', w.url), 1, w.deschtml, true from code_game_worlds as w left join post on post.subsite=\'code\' and post.url=concat(\'/code/games/#\', w.url) where post.id is null');
	?>
		<p>copied game worlds into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyApplicationsToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(r.released), concat(a.name, \' v\', r.major, \'.\', r.minor, \'.\', r.revision), \'code\', concat(\'/code/vs/\', a.url), 1, r.changelog, true from code_vs_applications as a left join code_vs_releases as r on r.application=a.id left join code_vs_releases as r_after on r_after.application=a.id and r.released<r_after.released left join post on post.subsite=\'code\' and post.url=concat(\'/code/vs/\', a.url) where post.id is null and r_after.id is null');
	?>
		<p>copied applications into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyScriptsToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(s.released), s.name, \'code\', concat(\'/code/web/\', s.url), 1, s.deschtml, true from code_web_scripts as s left join post as p on p.subsite=\'code\' and p.url=concat(\'/code/web/\', s.url) where p.id is null');
	?>
		<p>copied web scripts into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyToCalcProg(): void {
		self::$db->real_query('insert into calcprog (id, post, subject, model, ticalc, description) select cp.url, p.id, cs.name, cm.name, cp.ticalc, cp.deschtml from code_calc_progs as cp left join post as p on p.url=concat(\'/code/calc/#\', cp.url) left join code_calc_subject as cs on cs.id=cp.subject left join code_calc_model as cm on cm.id=cp.model left join calcprog on calcprog.id=cp.url where calcprog.id is null');
	?>
		<p>copied calculator programs into new <code>calcprog</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyToGameworlds(): void {
		self::$db->real_query('insert into gameworld (id, post, engine, markdown, description, dmzx) select w.url, p.id, e.name, w.descmd, w.deschtml, w.dmzx from code_game_worlds as w left join post as p on p.url=concat(\'/code/games/#\', w.url) left join code_game_engines as e on e.id=w.engine left join gameworld on gameworld.id=w.url where gameworld.id is null');
	?>
		<p>copied game worlds into new <code>gameworld</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyToApplications(): void {
		self::$db->real_query('insert into application (id, name, post, github, wiki, markdown, description) select a.url, a.name, p.id, a.github, a.wiki, a.descmd, a.deschtml from code_vs_applications as a left join post as p on p.url=concat(\'/code/vs/\', a.url) left join application on application.id=a.url where application.id is null');
	?>
		<p>copied applications into new <code>application</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyToReleases(): void {
		self::$db->real_query('insert into `release` (application, major, minor, revision, instant, language, dotnet, visualstudio, binurl, bin32url, srcurl, changelog) select a.url, r.major, r.minor, r.revision, from_unixtime(r.released), l.abbr, n.version, case s.abbr when \'vb6\' then 6 else s.abbr end, r.binurl, ifnull(r.bin32url, \'\'), r.srcurl, r.changelog from code_vs_releases as r left join code_vs_applications as a on a.id=r.application left join code_vs_lang as l on l.id=r.lang left join code_vs_dotnet as n on n.id=r.dotnet left join code_vs_studio as s on s.version=r.studio left join `release` on release.application=a.url and release.major=r.major and release.minor=r.minor and release.revision=r.revision where release.application is null');
	?>
		<p>copied releases into new <code>release</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyToScripts(): void {
		self::$db->real_query('insert into script (id, post, type, download, github, wiki, mddescription, description, mdinstructions, instructions)
		select s.url, p.id, t.name, s.download, s.github, s.wiki, s.descmd, s.deschtml, s.instmd, s.insthtml from code_web_scripts as s left join code_web_usetype as t on t.id=s.usetype left join post as p on p.url=concat(\'/code/web/\', s.url) left join script on script.id=s.url where script.id is null');
	?>
		<p>copied web scripts into new <code>script</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyApplicationComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(ac.posted), a.post, ac.user, ac.name, ac.contacturl, ac.html, ac.markdown from code_vs_comments as ac left join code_vs_applications as oa on oa.id=ac.application left join application as a on a.id=oa.url left join comment as c on c.post=a.post and c.instant=from_unixtime(ac.posted) where c.id is null');
	?>
		<p>copied application comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyScriptComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(sc.posted), s.post, sc.user, sc.name, sc.contacturl, sc.html, sc.markdown from code_web_comments as sc left join code_web_scripts as os on os.id=sc.script left join script as s on s.id=os.url left join comment as c on c.post=s.post and c.instant=from_unixtime(sc.posted) where c.id is null');
	?>
		<p>copied web script comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteApplicationCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists code_vs_comment_added');
		self::$db->real_query('drop trigger if exists code_vs_comment_changed');
		self::$db->real_query('drop trigger if exists code_vs_comment_deleted');
	?>
		<p>deleted old application comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteApplicationTrigger(): void {
		self::$db->real_query('drop trigger if exists code_vs_application_changed');
	?>
		<p>deleted old application trigger. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteReleaseTrigger(): void {
		self::$db->real_query('drop trigger if exists code_vs_release_added');
	?>
		<p>deleted old application release trigger. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteApplicationProcedures(): void {
		self::$db->real_query('drop procedure if exists ListApplicationReleases');
		self::$db->real_query('drop procedure if exists ListApplications');
	?>
		<p>deleted old application procedures. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteScriptCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists code_web_comment_added');
		self::$db->real_query('drop trigger if exists code_web_comment_changed');
		self::$db->real_query('drop trigger if exists code_web_comment_deleted');
	?>
		<p>deleted old web script comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteScriptTriggers(): void {
		self::$db->real_query('drop trigger if exists code_web_script_added');
		self::$db->real_query('drop trigger if exists code_web_script_changed');
	?>
		<p>deleted old web script triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldCalc(): void {
		self::$db->real_query('drop table if exists code_calc_progs');
		self::$db->real_query('drop table if exists code_calc_model');
		self::$db->real_query('drop table if exists code_calc_subject');
	?>
		<p>deleted old calculator program tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldGameworlds(): void {
		self::$db->real_query('drop table if exists code_game_worlds');
		self::$db->real_query('drop table if exists code_game_engines');
	?>
		<p>deleted old gameworld tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldApplications(): void {
		self::$db->real_query('drop table if exists code_vs_comments');
		self::$db->real_query('drop table if exists code_vs_releases');
		self::$db->real_query('drop table if exists code_vs_studio');
		self::$db->real_query('drop table if exists code_vs_lang');
		self::$db->real_query('drop table if exists code_vs_dotnet');
		self::$db->real_query('drop table if exists code_vs_applications');
	?>
		<p>deleted old application tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldScripts(): void {
		self::$db->begin_transaction();
		self::$db->real_query('drop table if exists code_web_comments');
		self::$db->real_query('drop table if exists code_web_requirements');
		self::$db->real_query('drop table if exists code_web_scripts');
		self::$db->real_query('drop table if exists code_web_reqinfo');
		self::$db->real_query('drop table if exists code_web_usetype');
		self::$db->commit();
	?>
		<p>deleted old web script tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl in(\'code_vs_releases\', \'code_web_scripts\', \'code_web_comments\')');
	?>
		<p>deleted old code contributions. refresh the page to take the next step.</p>
<?php
	}
}
new CodeTransition();
