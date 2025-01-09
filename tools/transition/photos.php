<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class PhotoTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('album', 'photo album', 'see my photos', 'photoed');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from photos left join post on post.subsite=\'album\' and post.url=concat(\'/album/\', photos.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyPhotosToPost();
			else {
?>
				<p>all old photos exist in new <code>post</code> table.</p>
			<?php
				self::CheckPhotoTable();
			}
		} else {
			?>
			<p>old photos table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckPhotoTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photo\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>photo</code> table exists.</p>
		<?php
			self::CheckPhotoRows();
		} else
			self::CreateTable('photo');
	}

	private static function CheckPhotoRows(): void {
		$missing = self::$db->query('select 1 from photos left join photo on photo.id=photos.url where photo.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyPhotosToPhoto();
		else {
		?>
			<p>all old photos exist in new <code>photo</code> table.</p>
			<?php
			self::CheckTagTable();
		}
	}

	protected static function CheckTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_tags\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from photos_tags as pt left join tag as t on t.name=pt.name and t.subsite=\'album\' where t.name is null limit 1');
			if ($missing->fetch_column())
				self::CopyPhotoTags();
			else {
			?>
				<p>all old photo tags exist in new <code>tag</code> table.</p>
			<?php
				self::CheckPostTagTable();
			}
		} else {
			?>
			<p>old photo tags table no longer exists.</p>
			<?php
			self::CheckCommentTriggers();
		}
	}

	protected static function CheckPostTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_taglinks\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from photos_taglinks as pl left join photos as op on op.id=pl.photo left join photo as ph on ph.id=op.url left join photos_tags as pt on pt.id=pl.tag left join post_tag as npt on npt.post=ph.post and npt.tag=pt.name where npt.post is null limit 1');
			if ($missing->fetch_column())
				self::CopyPhotoPostTags();
			else {
			?>
				<p>all old photo tagging exists in new <code>post_tag</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old photo tagging table no longer exists.</p>
			<?php
			self::CheckOldTags();
		}
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from photos_comments as pc left join photos as op on op.id=pc.photo left join photo as ph on ph.id=op.url left join comment as c on c.post=ph.post and c.instant=from_unixtime(pc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyPhotoComments();
			else {
			?>
				<p>all old photo comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckOldTagLinks();
			}
		} else {
			?>
			<p>old photo comments table no longer exists.</p>
		<?php
			self::CheckOldPhotos();
		}
	}

	private static function CheckOldTagLinks(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_taglinks\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTagLinks();
		else {
		?>
			<p>old photo tagging table no longer exists.</p>
		<?php
			self::CheckOldTags();
		}
	}

	private static function CheckOldTags(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_tags\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTags();
		else {
		?>
			<p>old photo tags table no longer exists.</p>
		<?php
			self::CheckCommentTriggers();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'photos_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old photo comment triggers no longer exist.</p>
		<?php
			self::CheckPhotoTriggers();
		}
	}

	private static function CheckPhotoTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'photos\' limit 1');
		if ($exists->fetch_column())
			self::DeletePhotoTriggers();
		else {
		?>
			<p>old photo triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'photos\' or srctbl=\'photos_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>photo contributions no longer exist.</p>
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
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old photo comments table no longer exists.</p>
		<?php
			self::CheckOldPhotos();
		}
	}

	private static function CheckOldPhotos(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'photos\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldPhotos();
		else {
		?>
			<p>old photos table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyPhotosToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(op.posted), op.caption, \'album\', concat(\'/album/\', op.url), 1, concat(\'<p><img class=photo src="/album/photos/\', op.url, \'.jpeg"></p>\'), true from photos as op left join post on post.subsite=\'album\' and post.url=concat(\'/album/\', op.url) where post.id is null');
		?>
		<p>copied photos into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotosToPhoto(): void {
		self::$db->real_query('insert into photo (id, post, youtube, taken, year, story, storymd) select ph.url, ps.id, ph.youtube, from_unixtime(ph.taken), ph.year, ph.story, ph.storymd from photos as ph left join post as ps on ps.url=concat(\'/album/\', ph.url) left join photo on photo.id=ph.url where photo.id is null');
	?>
		<p>copied photos into new <code>photo</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotoTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select pt.name, \'album\', pt.description from photos_tags as pt left join tag on tag.name=pt.name where tag.name is null');
	?>
		<p>copied photo tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotoPostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select ph.post, pt.name from photos_taglinks as pl left join photos as op on op.id=pl.photo left join photo as ph on ph.id=op.url left join photos_tags as pt on pt.id=pl.tag left join post_tag npt on npt.post=ph.post and npt.tag=pt.name where npt.post is null');
	?>
		<p>copied photo tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotoComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(pc.posted), ph.post, pc.user, pc.name, pc.contacturl, pc.html, pc.markdown from photos_comments as pc left join photos as op on op.id=pc.photo left join photo as ph on ph.id=op.url left join comment as c on c.post=ph.post and c.instant=from_unixtime(pc.posted) where c.id is null');
	?>
		<p>copied photo comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTagLinks(): void {
		self::$db->real_query('drop table photos_taglinks');
	?>
		<p>deleted old photo tagging table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTags(): void {
		self::$db->real_query('drop table photos_tags');
	?>
		<p>deleted old photo tag table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists photo_comment_added');
		self::$db->real_query('drop trigger if exists photo_comment_changed');
		self::$db->real_query('drop trigger if exists photo_comment_deleted');
	?>
		<p>deleted old photo comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeletePhotoTriggers(): void {
		self::$db->real_query('drop trigger if exists photo_added');
		self::$db->real_query('drop trigger if exists photo_changed');
	?>
		<p>deleted old photo triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'photos\' or srctbl=\'photos_comments\'');
	?>
		<p>deleted old photo contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table photos_comments');
	?>
		<p>deleted old photo comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldPhotos(): void {
		self::$db->real_query('drop table photos');
	?>
		<p>deleted old photos table. refresh the page to take the next step.</p>
<?php
	}
}
new PhotoTransition();
