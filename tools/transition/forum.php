<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class ForumTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('forum', 8, 'forum', 'forum', 'join or start conversations', 'forumed');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		if (self::CheckTableExists('forum_discussions')) {
			$missing = self::$db->query('select 1 from forum_discussions as d left join post as p on p.subsite=\'forum\' and p.title=d.title where p.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyForumToPost();
			else {
?>
				<p>all old forum discussions exist in new <code>post</code> table.</p>
			<?php
				self::CheckTagTable();
			}
		} else {
			?>
			<p>old forum discussions table no longer exists.</p>
			<?php
			self::Done();
		}
	}

	protected static function CheckTagRows(): void {
		if (self::CheckTableExists('forum_tags')) {
			$missing = self::$db->query('select 1 from forum_tags as ot left join tag as t on t.name=ot.name and t.subsite=\'forum\' where t.name is null limit 1');
			if ($missing->fetch_column())
				self::CopyForumTags();
			else {
			?>
				<p>all old forum tags exist in new <code>tag</code> table.</p>
			<?php
				self::CheckPostTagTable();
			}
		} else {
			?>
			<p>old forum tags table no longer exists.</p>
			<?php
			self::CheckOldDiscussions();
		}
	}

	protected static function CheckPostTagRows(): void {
		if (self::CheckTableExists('forum_discussion_tags')) {
			$missing = self::$db->query('select 1 from forum_discussion_tags as fdt left join forum_discussions as fd on fd.id=fdt.discussion left join post as p on p.subsite=\'forum\' and p.title=fd.title left join forum_tags as ft on ft.id=fdt.tag left join post_tag as pt on pt.post=p.id and pt.tag=ft.name where pt.post is null limit 1');
			if ($missing->fetch_column())
				self::CopyForumPostTags();
			else {
			?>
				<p>all old forum tagging exists in new <code>post_tag</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old forum tagging table no longer exists.</p>
			<?php
			self::CheckOldTags();
		}
	}

	protected static function CheckCommentRows(): void {
		if (self::CheckTableExists('forum_replies')) {
			$missing = self::$db->query('select 1 from forum_replies as fr left join forum_discussions as fd on fd.id=fr.discussion left join post as p on p.subsite=\'forum\' and p.title=fd.title left join comment as c on c.post=p.id and c.instant=from_unixtime(fr.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyForumComments();
			else {
			?>
				<p>all old forum replies exists in new <code>comment</code> table.</p>
			<?php
				self::CheckDiscussionView();
			}
		} else {
			?>
			<p>old forum reply table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckDiscussionView(): void {
		if (self::CheckViewExists('discussion')) {
		?>
			<p>new <code>discussion</code> view exists.</p>
		<?php
			self::CheckEditTable();
		} else
			self::CreateView('discussion');
	}

	private static function CheckEditTable(): void {
		if (self::CheckTableExists('edit')) {
		?>
			<p>new <code>edit</code> table exists.</p>
			<?php
			self::CheckEditRows();
		} else
			self::CreateTable('edit');
	}

	private static function CheckEditRows(): void {
		if (self::CheckTableExists('forum_edits')) {
			$missing = self::$db->query('select 1 from forum_edits as fe left join edit as e on e.instant=from_unixtime(fe.posted) and e.user=fe.editor where e.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyEdits();
			else {
			?>
				<p>all old forum edits exist in new <code>edit</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old forum edits table no longer exists.</p>
		<?php
			self::CheckCommentTriggers();
		}
	}

	private static function CheckCommentTriggers(): void {
		if (self::CheckTriggersExist('forum_replies'))
			self::DeleteReplyTriggers();
		else {
		?>
			<p>old forum reply triggers no longer exist.</p>
		<?php
			self::CheckDiscussionTriggers();
		}
	}

	private static function CheckDiscussionTriggers(): void {
		if (self::CheckTriggersExist('forum_discussions'))
			self::DeleteDiscussionTriggers();
		else {
		?>
			<p>old discussion triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		if (self::CheckTableExists('contributions')) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'forum_replies\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>forum contributions no longer exist.</p>
			<?php
				self::CheckOldEdits();
			}
		} else {
			?>
			<p>old contributions table no longer exists.</p>
		<?php
			self::CheckOldEdits();
		}
	}

	private static function CheckOldEdits(): void {
		if (self::CheckTableExists('forum_edits'))
			self::DeleteOldEdits();
		else {
		?>
			<p>old forum edit table no longer exists.</p>
		<?php
			self::CheckOldReplies();
		}
	}

	private static function CheckOldReplies(): void {
		if (self::CheckTableExists('forum_replies'))
			self::DeleteOldForumReplies();
		else {
		?>
			<p>old forum edit table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckOldTagLinks(): void {
		if (self::CheckTableExists('forum_discussion_tags'))
			self::DeleteOldTagLinks();
		else {
		?>
			<p>old forum tagging table no longer exists.</p>
		<?php
			self::CheckOldTags();
		}
	}

	private static function CheckOldTags(): void {
		if (self::CheckTableExists('forum_tags'))
			self::DeleteOldTags();
		else {
		?>
			<p>old forum tags table no longer exists.</p>
		<?php
			self::CheckOldDiscussions();
		}
	}

	private static function CheckOldDiscussions(): void {
		if (self::CheckTableExists('forum_discussions'))
			self::DeleteOldDiscussions();
		else {
		?>
			<p>old forum discussions table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyForumToPost(): void {
		self::$db->real_query('insert into post (published, instant, title, subsite, url, author, preview, hasmore) select true, from_unixtime(fr.posted), fd.title, \'forum\', concat(\'/forum/\', fd.id), 1, \'\', true from forum_discussions as fd left join forum_replies as fr on fd.id=fr.discussion left join forum_replies as lfr on lfr.discussion=fr.discussion and lfr.posted>fr.posted left join post as p on p.subsite=\'forum\' and p.title=fd.title where p.id is null and lfr.id is null');
		self::$db->real_query('update post set url=concat(\'/forum/\', id) where subsite=\'forum\'');
		?>
		<p>copied forum into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyForumTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select ft.name, \'forum\', ft.description from forum_tags as ft left join tag on tag.name=ft.name and tag.subsite=\'forum\' where tag.name is null');
	?>
		<p>copied forum tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyForumPostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select p.id, ft.name from forum_discussion_tags as fdt left join forum_discussions as fd on fd.id=fdt.discussion left join post as p on p.subsite=\'forum\' and p.title=fd.title left join forum_tags as ft on ft.id=fdt.tag left join post_tag as pt on pt.post=p.id and pt.tag=ft.name where pt.post is null');
	?>
		<p>copied forum tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyForumComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(fr.posted), p.id, fr.user, fr.name, fr.contacturl, fr.html, fr.markdown from forum_replies as fr left join forum_discussions as fd on fd.id=fr.discussion left join post as p on p.subsite=\'forum\' and p.title=fd.title left join comment as c on c.post=p.id and c.instant=from_unixtime(fr.posted) where c.id is null');
	?>
		<p>copied forum replies into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyEdits(): void {
		self::$db->real_query('insert into edit (comment, instant, user) select c.id, from_unixtime(fe.posted), fe.editor from forum_edits as fe left join forum_replies as fr on fr.id=fe.reply left join forum_discussions as fd on fd.id=fr.discussion left join post as p on p.subsite=\'forum\' and p.title=fd.title left join comment as c on c.post=p.id and c.instant=from_unixtime(fr.posted) left join edit as e on e.user=fe.editor and e.comment=c.id and e.instant=from_unixtime(fe.posted)');
	?>
		<p>copied forum edits into new <code>edit</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteReplyTriggers(): void {
		self::$db->real_query('drop trigger if exists forum_reply_added');
		self::$db->real_query('drop trigger if exists forum_reply_changed');
		self::$db->real_query('drop trigger if exists forum_reply_deleted');
	?>
		<p>deleted old forum reply triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteDiscussionTriggers(): void {
		self::$db->real_query('drop trigger if exists forum_discussion_changed');
	?>
		<p>deleted old forum discussion triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'forum_replies\'');
	?>
		<p>deleted old forum contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldEdits(): void {
		self::$db->real_query('drop table forum_edits');
	?>
		<p>deleted old forum edits table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldForumReplies(): void {
		self::$db->real_query('drop table forum_replies');
	?>
		<p>deleted old forum replies table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTagLinks(): void {
		self::$db->real_query('drop table forum_discussion_tags');
	?>
		<p>deleted old forum tagging table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTags(): void {
		self::$db->real_query('drop table forum_tags');
	?>
		<p>deleted old forum tag table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldDiscussions(): void {
		self::$db->real_query('drop table forum_discussions');
	?>
		<p>deleted old forum discussions table. refresh the page to take the next step.</p>
<?php
	}
}
new ForumTransition();
