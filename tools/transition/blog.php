<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class BlogTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('bln', 1, 'post', 'blog', 'read the blog', 'blogged');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_entries\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from blog_entries left join post on post.subsite=\'bln\' and post.url=concat(\'/bln/\', blog_entries.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyBlogToPost();
			else {
?>
				<p>all old blog entries exist in new <code>post</code> table.</p>
			<?php
				self::CheckBlogTable();
			}
		} else {
			?>
			<p>old blog table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckBlogTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>blog</code> table exists.</p>
		<?php
			self::CheckBlogRows();
		} else
			self::CreateTable('blog');
	}

	private static function CheckBlogRows(): void {
		$missing = self::$db->query('select 1 from blog_entries left join blog on blog.id=blog_entries.url where blog.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyEntriesToBlog();
		else {
		?>
			<p>all old blog entries exist in new <code>blog</code> table.</p>
			<?php
			self::CheckTagTable();
		}
	}

	protected static function CheckTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_tags\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from blog_tags as ot left join tag as t on t.name=ot.name and t.subsite=\'bln\' where t.name is null limit 1');
			if ($missing->fetch_column())
				self::CopyBlogTags();
			else {
			?>
				<p>all old blog tags exist in new <code>tag</code> table.</p>
			<?php
				self::CheckPostTagTable();
			}
		} else {
			?>
			<p>old blog tags table no longer exists.</p>
			<?php
			self::CheckOldBlog();
		}
	}

	protected static function CheckPostTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_entrytags\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from blog_entrytags as bet left join blog_entries as ob on ob.id=bet.entry left join post as p on p.url=concat(\'/bln/\', ob.url) left join blog_tags as obt on obt.id=bet.tag left join post_tag as npt on npt.post=p.id and npt.tag=obt.name where npt.post is null limit 1');
			if ($missing->fetch_column())
				self::CopyBlogPostTags();
			else {
			?>
				<p>all old blog tagging exists in new <code>post_tag</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old blog tagging table no longer exists.</p>
			<?php
			self::CheckOldTags();
		}
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from blog_comments as bc left join blog_entries as ob on ob.id=bc.entry left join blog as b on b.id=ob.url left join comment as c on c.post=b.post and c.instant=from_unixtime(bc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyBlogComments();
			else {
			?>
				<p>all old blog comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old blog comment table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'blog_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old blog comment triggers no longer exist.</p>
		<?php
			self::CheckEntryTriggers();
		}
	}

	private static function CheckEntryTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'blog_entries\' limit 1');
		if ($exists->fetch_column())
			self::DeleteEntryTriggers();
		else {
		?>
			<p>old blog triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'blog_entries\' or srctbl=\'blog_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>blog contributions no longer exist.</p>
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
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old blog comment table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckOldTagLinks(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_entrytags\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTagLinks();
		else {
		?>
			<p>old blog tagging table no longer exists.</p>
		<?php
			self::CheckOldTags();
		}
	}

	private static function CheckOldTags(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_tags\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTags();
		else {
		?>
			<p>old blog tags table no longer exists.</p>
		<?php
			self::CheckOldBlog();
		}
	}

	private static function CheckOldBlog(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'blog_entries\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldBlog();
		else {
		?>
			<p>old blog table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyBlogToPost(): void {
		self::$db->real_query('insert into post (published, instant, title, subsite, url, author, preview, hasmore) select ob.status=\'published\', from_unixtime(ob.posted), ob.title, \'bln\', concat(\'/bln/\', ob.url), 1, left(ob.content, locate(\'</p>\', ob.content) + 3) as preview, length(ob.content) - length(replace(ob.content, \'</p>\', \'\')) > 4 from blog_entries as ob left join post on post.subsite=\'bln\' and post.url=concat(\'/bln/\', ob.url) where post.id is null');
		?>
		<p>copied blog into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyEntriesToBlog(): void {
		self::$db->real_query('insert into blog (id, post, html, markdown) select ob.url, p.id, ob.content, ob.markdown from blog_entries as ob left join post as p on p.url=concat(\'/bln/\', ob.url) left join blog on blog.id=ob.url where blog.id is null');
	?>
		<p>copied blog entries into new <code>blog</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyBlogTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select ot.name, \'bln\', concat(\'<p>blog entries \', ot.description, \'</p>\') from blog_tags as ot left join tag on tag.name=ot.name and tag.subsite=\'bln\' where tag.name is null');
	?>
		<p>copied blog tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyBlogPostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select b.post, bt.name from blog_entrytags as bet left join blog_entries as ob on ob.id=bet.entry left join blog as b on b.id=ob.url left join blog_tags as bt on bt.id=bet.tag left join post_tag npt on npt.post=b.post and npt.tag=bt.name where npt.post is null');
	?>
		<p>copied blog tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyBlogComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(bc.posted), b.post, bc.user, bc.name, bc.contacturl, bc.html, bc.markdown from blog_comments as bc left join blog_entries as ob on ob.id=bc.entry left join blog as b on b.id=ob.url left join comment as c on c.post=b.post and c.instant=from_unixtime(bc.posted) where c.id is null');
	?>
		<p>copied blog comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists blog_comment_added');
		self::$db->real_query('drop trigger if exists blog_comment_changed');
		self::$db->real_query('drop trigger if exists blog_comment_deleted');
	?>
		<p>deleted old blog comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteEntryTriggers(): void {
		self::$db->real_query('drop trigger if exists blog_entry_added');
		self::$db->real_query('drop trigger if exists blog_entry_changed');
		self::$db->real_query('drop trigger if exists blog_entry_deleted');
	?>
		<p>deleted old blog entry triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'blog_entries\' or srctbl=\'blog_comments\'');
	?>
		<p>deleted old blog contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table blog_comments');
	?>
		<p>deleted old blog comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTagLinks(): void {
		self::$db->real_query('drop table blog_entrytags');
	?>
		<p>deleted old blog tagging table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTags(): void {
		self::$db->real_query('drop table blog_tags');
	?>
		<p>deleted old blog tag table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldBlog(): void {
		self::$db->real_query('drop table blog_entries');
	?>
		<p>deleted old blog table. refresh the page to take the next step.</p>
<?php
	}
}
new BlogTransition();
