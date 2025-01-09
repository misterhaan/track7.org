<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class GuidesTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('guides', 3, 'guide', 'guides', 'learn how i’ve done things', 'guided');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guides\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from guides left join post on post.subsite=\'guides\' and post.url=concat(\'/guides/\', guides.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyGuidesToPost();
			else {
?>
				<p>all old guides exist in new <code>post</code> table.</p>
			<?php
				self::CheckGuideTable();
			}
		} else {
			?>
			<p>old guides table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckGuideTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>guide</code> table exists.</p>
		<?php
			self::CheckGuideRows();
		} else
			self::CreateTable('guide');
	}

	private static function CheckGuideRows(): void {
		$missing = self::$db->query('select 1 from guides left join guide on guide.id=guides.url where guide.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyGuides();
		else {
		?>
			<p>all old guides exist in new <code>guide</code> table.</p>
		<?php
			self::CheckChapterTable();
		}
	}

	private static function CheckChapterTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'chapter\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>chapter</code> table exists.</p>
		<?php
			self::CheckChapterRows();
		} else
			self::CreateTable('chapter');
	}

	private static function CheckChapterRows(): void {
		$missing = self::$db->query('select 1 from guide_pages as op left join guides as og on og.id=op.guide left join chapter as np on np.guide=og.url and np.number=op.number where np.number is null limit 1');
		if ($missing->fetch_column())
			self::CopyChapters();
		else {
		?>
			<p>all old guide pages exist in new <code>chapter</code> table.</p>
			<?php
			self::CheckTagTable();
		}
	}

	protected static function CheckTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_tags\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from guide_tags as ot left join tag as t on t.name=ot.name and t.subsite=\'guides\' where t.name is null limit 1');
			if ($missing->fetch_column())
				self::CopyGuideTags();
			else {
			?>
				<p>all old guide tags exist in new <code>tag</code> table.</p>
			<?php
				self::CheckPostTagTable();
			}
		} else {
			?>
			<p>old guide tags table no longer exists.</p>
			<?php
			self::CheckOldGuides();
		}
	}

	protected static function CheckPostTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_taglinks\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from guide_taglinks as gtl left join guides as og on og.id=gtl.guide left join post as p on p.url=concat(\'/guides/\', og.url) left join guide_tags as ogt on ogt.id=gtl.tag left join post_tag as npt on npt.post=p.id and npt.tag=ogt.name where npt.post is null limit 1');
			if ($missing->fetch_column())
				self::CopyGuidePostTags();
			else {
			?>
				<p>all old guide tagging exists in new <code>post_tag</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old guide tagging table no longer exists.</p>
			<?php
			self::CheckOldTags();
		}
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from guide_comments as gc left join guides as og on og.id=gc.guide left join guide as g on g.id=og.url left join comment as c on c.post=g.post and c.instant=from_unixtime(gc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyGuideComments();
			else {
			?>
				<p>all old guide comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckVoteTable();
			}
		} else {
			?>
			<p>old guide comment table no longer exists.</p>
			<?php
			self::CheckOldTagLinks();
		}
	}

	protected static function CheckVoteRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_votes\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from guide_votes as gv left join guides as og on og.id=gv.guide left join post as p on p.url=concat(\'/guides/\', og.url) left join vote as v on v.post=p.id and (gv.voter>0 and v.user=gv.voter or gv.ip>0 and v.ip=gv.ip) where v.vote is null limit 1');
			if ($missing->fetch_column())
				self::CopyGuideVotes();
			else {
			?>
				<p>all old guide votes exists in new <code>vote</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old guide votes table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'guide_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old guide comment triggers no longer exist.</p>
		<?php
			self::CheckGuideTriggers();
		}
	}

	private static function CheckGuideTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'guides\' limit 1');
		if ($exists->fetch_column())
			self::DeleteGuideTriggers();
		else {
		?>
			<p>old guide triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'guides\' or srctbl=\'guide_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>guide contributions no longer exist.</p>
			<?php
				self::CheckOldVotes();
			}
		} else {
			?>
			<p>old contributions table no longer exists.</p>
		<?php
			self::CheckOldVotes();
		}
	}

	private static function CheckOldVotes(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_votes\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldVotes();
		else {
		?>
			<p>old guide votes table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckOldComments(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old guide comments table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckOldTagLinks(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_taglinks\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTagLinks();
		else {
		?>
			<p>old guide tagging table no longer exists.</p>
		<?php
			self::CheckOldTags();
		}
	}

	private static function CheckOldTags(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_tags\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTags();
		else {
		?>
			<p>old guide tags table no longer exists.</p>
		<?php
			self::CheckOldPages();
		}
	}

	private static function CheckOldPages(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guide_pages\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldPages();
		else {
		?>
			<p>old guide pages table no longer exists.</p>
		<?php
			self::CheckOldGuides();
		}
	}

	private static function CheckOldGuides(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'guides\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldGuides();
		else {
		?>
			<p>old guides table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyGuidesToPost(): void {
		self::$db->real_query('insert into post (published, instant, title, subsite, url, author, preview, hasmore) select g.status=\'published\', from_unixtime(g.posted), g.title, \'guides\', concat(\'/guides/\', g.url), 1, g.summary, true from guides as g left join post on post.subsite=\'guides\' and post.url=concat(\'/guides/\', g.url) where post.id is null');
		?>
		<p>copied guides into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGuides(): void {
		self::$db->real_query('insert into guide (id, post, summary, updated, level, views) select og.url, p.id, og.summary_markdown, from_unixtime(og.updated), og.level, og.views from guides as og left join post as p on p.url=concat(\'/guides/\', og.url) left join guide as g on g.id=og.url where g.id is null');
	?>
		<p>copied guides into new <code>guide</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyChapters(): void {
		self::$db->real_query('insert into chapter (guide, number, title, html, markdown) select og.url, op.number, op.heading, op.html, op.markdown from guide_pages as op left join guides as og on og.id=op.guide left join chapter as np on np.guide=og.url and np.number=op.number where np.guide is null');
	?>
		<p>copied guide pages into new <code>chapter</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGuideTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select ot.name, \'guides\', if(ot.description is null, \'\', concat(\'<p>showing guides dealing with \', ot.description, \'</p>\')) from guide_tags as ot left join tag on tag.name=ot.name and tag.subsite=\'guides\' where tag.name is null');
	?>
		<p>copied guide tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGuidePostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select g.post, gt.name from guide_taglinks as gtl left join guides as og on og.id=gtl.guide left join guide as g on g.id=og.url left join guide_tags as gt on gt.id=gtl.tag left join post_tag npt on npt.post=g.post and npt.tag=gt.name where npt.post is null');
	?>
		<p>copied guide tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGuideComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(gc.posted), g.post, gc.user, gc.name, gc.contacturl, gc.html, gc.markdown from guide_comments as gc left join guides as og on og.id=gc.guide left join guide as g on g.id=og.url left join comment as c on c.post=g.post and c.instant=from_unixtime(gc.posted) where c.id is null');
	?>
		<p>copied guide comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyGuideVotes(): void {
		self::$db->query('insert into vote (post, user, ip, instant, vote) select p.id, gv.voter, gv.ip, from_unixtime(gv.posted), gv.vote from guide_votes as gv left join guides as og on og.id=gv.guide left join post as p on p.url=concat(\'/guides/\', og.url) left join vote as v on v.post=p.id and (gv.voter>0 and v.user=gv.voter or gv.ip>0 and v.ip=gv.ip) where v.vote is null');
	?>
		<p>copied guide votes into new <code>vote</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists guide_comment_added');
		self::$db->real_query('drop trigger if exists guide_comment_changed');
		self::$db->real_query('drop trigger if exists guide_comment_deleted');
	?>
		<p>deleted old guide comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteGuideTriggers(): void {
		self::$db->real_query('drop trigger if exists guide_added');
		self::$db->real_query('drop trigger if exists guide_changed');
		self::$db->real_query('drop trigger if exists guide_deleted');
	?>
		<p>deleted old guide triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'guides\' or srctbl=\'guide_comments\'');
	?>
		<p>deleted old guide contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldVotes(): void {
		self::$db->real_query('drop table guide_votes');
	?>
		<p>deleted old guide votes table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table guide_comments');
	?>
		<p>deleted old guide comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTagLinks(): void {
		self::$db->real_query('drop table guide_taglinks');
	?>
		<p>deleted old guide tagging table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTags(): void {
		self::$db->real_query('drop table guide_tags');
	?>
		<p>deleted old guide tag table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldPages(): void {
		self::$db->real_query('drop table guide_pages');
	?>
		<p>deleted old guide pages table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldGuides(): void {
		self::$db->real_query('drop table guides');
	?>
		<p>deleted old guides table. refresh the page to take the next step.</p>
<?php
	}
}
new GuidesTransition();
