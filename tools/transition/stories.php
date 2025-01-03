<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class StoriesTransition extends TransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('pen', 'stories', 'read short fiction and a poem', 'storied');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'stories\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from stories left join post on post.subsite=\'pen\' and post.url=concat(\'/pen/\', stories.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyStoriesToPost();
			else {
?>
				<p>all old stories exist in new <code>post</code> table.</p>
			<?php
				self::CheckSeriesTable();
			}
		} else {
			?>
			<p>old stories table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckSeriesTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'series\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>series</code> table exists.</p>
			<?php
			self::CheckSeriesRows();
		} else
			self::CreateTable('series');
	}

	private static function CheckSeriesRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'stories_series\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from stories_series left join series on series.id=stories_series.url where series.id is null limit 1');
			if ($missing->fetch_column())
				self::CopySeries();
			else {
			?>
				<p>all old series exist in new <code>series</code> table.</p>
			<?php
				self::CheckStoryTable();
			}
		} else {
			?>
			<p>old series table no longer exists.</p>
		<?php
			self::CheckStoryTable();
		}
	}

	private static function CheckStoryTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'story\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>story</code> table exists.</p>
		<?php
			self::CheckStoryRows();
		} else
			self::CreateTable('story');
	}

	protected static function CheckStoryRows(): void {
		$missing = self::$db->query('select 1 from stories left join story on story.id=stories.url where story.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyStories();
		else {
		?>
			<p>all old stories exist in new <code>story</code> table.</p>
			<?php
			self::CheckTagUsageView();
		}
	}

	protected static function CheckTagRows(): void {
		throw new DetailedException('Stories do not use tags, so CheckTagRows() is not implemented.');
	}

	protected static function CheckPostTagRows(): void {
		throw new DetailedException('Stories do not use tags, so CheckPostTagRows() is not implemented.');
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'stories_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from stories_comments as sc left join stories as os on os.id=sc.story left join story as s on s.id=os.url left join comment as c on c.post=s.post and c.instant=from_unixtime(sc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyStoryComments();
			else {
			?>
				<p>all old story comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old story comment table no longer exists.</p>
		<?php
			self::CheckContributions();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'stories_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old story comment triggers no longer exist.</p>
		<?php
			self::CheckStoryTriggers();
		}
	}

	private static function CheckStoryTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'stories\' limit 1');
		if ($exists->fetch_column())
			self::DeleteStoryTriggers();
		else {
		?>
			<p>old story triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'stories\' or srctbl=\'stories_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>story contributions no longer exist.</p>
			<?php
				self::CheckOldComments();
			}
		} else {
			?>
			<p>old contributions table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckOldComments(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'stories_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old story comment table no longer exists.</p>
		<?php
			self::CheckOldStories();
		}
	}

	private static function CheckOldStories(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and (table_name=\'stories\' or table_name=\'stories_series\') limit 1');
		if ($exists->fetch_column())
			self::DeleteOldStories();
		else {
		?>
			<p>old stories table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyStoriesToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(s.posted), s.title, \'pen\', concat(\'/pen/\', s.url), 1, s.deschtml, true from stories as s left join post on post.subsite=\'pen\' and post.url=concat(\'/pen/\', s.url) where post.id is null');
		?>
		<p>copied stories into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopySeries(): void {
		self::$db->real_query('insert into series (id, title, markdown, html) select os.url, os.title, os.descmd, os.deschtml from stories_series as os left join series as s on s.id=os.url where s.id is null');
	?>
		<p>copied series into new <code>series</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyStories(): void {
		self::$db->real_query('insert into story (id, post, series, number, description, markdown, html) select s.url, p.id, ss.url, s.number, s.descmd, s.storymd, s.storyhtml from stories as s left join post as p on p.url=concat(\'/pen/\', s.url) left join stories_series as ss on ss.id=s.series left join story as ns on ns.id=s.url where ns.id is null');
	?>
		<p>copied stories into new <code>story</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyStoryComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(sc.posted), s.post, sc.user, sc.name, sc.contacturl, sc.html, sc.markdown from stories_comments as sc left join stories as os on os.id=sc.story left join story as s on s.id=os.url left join comment as c on c.post=s.post and c.instant=from_unixtime(sc.posted) where c.id is null');
	?>
		<p>copied story comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists story_comment_added');
		self::$db->real_query('drop trigger if exists story_comment_changed');
		self::$db->real_query('drop trigger if exists story_comment_deleted');
	?>
		<p>deleted old story comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteStoryTriggers(): void {
		self::$db->real_query('drop trigger if exists story_added');
		self::$db->real_query('drop trigger if exists story_changed');
		self::$db->real_query('drop trigger if exists story_deleted');
	?>
		<p>deleted old story triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'stories\' or srctbl=\'stories_comments\'');
	?>
		<p>deleted old story contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table stories_comments');
	?>
		<p>deleted old story comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldStories(): void {
		self::$db->real_query('drop table if exists stories');
		self::$db->real_query('drop table if exists stories_series');
	?>
		<p>deleted old story tables. refresh the page to take the next step.</p>
	<?php
	}

	private static function Done(): void {
	?>
		<p>done migrating stories, at least for now!</p>
<?php
	}
}
new StoriesTransition();
