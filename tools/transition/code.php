<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class CodeTransition extends TransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('code', 'software', 'download free software with source code', 'coded');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		self::CheckCalcPostRows();
	}

	private static function CheckCalcPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'code_calc_progs\' limit 1');
		if ($exists->fetch_column()) {
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
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'code_game_worlds\' limit 1');
		if ($exists->fetch_column()) {
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
			// TODO:  check other code post rows
			self::Done();
		}
	}

	private static function CheckCalcTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'calcprog\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>calcprog</code> table exists.</p>
		<?php
			self::CheckCalcRows();
		} else
			self::CreateTable('calcprog');
	}

	private static function CheckGameworldTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'gameworld\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>gameworld</code> table exists.</p>
		<?php
			self::CheckGameworldRows();
		} else
			self::CreateTable('gameworld');
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
		// TODO:  check comments for code that has comments
		self::CheckOldCalc();
		return;
		/*
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_comments\' limit 1');
		if ($exists->fetch_column()) {

			$missing = self::$db->query('select 1 from blog_comments as bc left join blog_entries as ob on ob.id=bc.entry left join blog as b on b.id=ob.url left join comment as c on c.post=b.post and c.instant=from_unixtime(bc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyBlogComments();
			else {
			?>
				<p>all old blog comments exists in new <code>comment</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old blog comment table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
			*/
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

	private static function Done(): void {
	?>
		<p>done migrating code, at least for now!</p>
<?php
	}
}
new CodeTransition();
