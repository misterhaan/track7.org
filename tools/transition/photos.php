<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class PhotoTransition extends Page {
	public function __construct() {
		parent::__construct('photo migration');
	}

	protected static function MainContent(): void {
?>
		<h1>photo migration</h1>
		<?php
		self::RequireDatabase();
		self::CheckUserTable();
	}

	private static function CheckUserTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'user\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>user</code> table exists.</p>
		<?php
			self::CheckUserRow();
		} else
			self::UserSetupLink();
	}

	private static function CheckUserRow(): void {
		$exists = self::$db->query('select 1 from user where id=1 limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>photo author exists in new <code>user</code> table.</p>
		<?php
			self::CheckSubsiteTable();
		} else
			self::UserSetupLink();
	}

	private static function CheckSubsiteTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'subsite\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>subsite</code> table exists.</p>
		<?php
			self::CheckSubsiteRow();
		} else
			self::CreateSubsiteTable();
	}

	private static function CheckSubsiteRow(): void {
		$exists = self::$db->query('select 1 from subsite where id=\'album\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>photo album exists in new <code>subsite</code> table.</p>
		<?php
			self::CheckPostTable();
		} else
			self::CreateSubsiteRow();
	}

	private static function CheckPostTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'post\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>post</code> table exists.</p>
		<?php
			self::CheckPostRows();
		} else
			self::CreatePostTable();
	}

	private static function CheckPostRows(): void {
		$missing = self::$db->query('select 1 from photos left join post on post.subsite=\'album\' and post.url=concat(\'/album/\', photos.url) where post.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyPhotosToPost();
		else {
		?>
			<p>all old photos exist in new <code>post</code> table.</p>
		<?php
			self::CheckPhotoTable();
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
			self::CreatePhotoTable();
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

	private static function CheckTagTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'tag\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>tag</code> table exists.</p>
		<?php
			self::CheckTagRows();
		} else
			self::CreateTagTable();
	}

	private static function CheckTagRows(): void {
		$missing = self::$db->query('select 1 from photos_tags as pt left join tag as t on t.name=pt.name and t.subsite=\'album\' where t.name is null limit 1');
		if ($missing->fetch_column())
			self::CopyPhotoTags();
		else {
		?>
			<p>all old photo tags exist in new <code>tag</code> table.</p>
		<?php
			self::CheckPostTagTable();
		}
	}

	private static function CheckPostTagTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'post_tag\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>post_tag</code> table exists.</p>
		<?php
			self::CheckPostTagRows();
		} else
			self::CreatePostTagTable();
	}

	private static function CheckPostTagRows(): void {
		$missing = self::$db->query('select 1 from photos_taglinks as pl left join photos as op on op.id=pl.photo left join photo as ph on ph.id=op.url left join photos_tags as pt on pt.id=pl.tag left join post_tag as npt on npt.post=ph.post and npt.tag=pt.name where npt.post is null limit 1');
		if ($missing->fetch_column())
			self::CopyPhotoPostTags();
		else {
		?>
			<p>all old photo tagging exists in new <code>post_tag</code> table.</p>
		<?php
			self::CheckTagUsageView();
		}
	}

	private static function CheckTagUsageView(): void {
		$exists = self::$db->query('select 1 from information_schema.views where table_schema=\'track7\' and table_name=\'tagusage\' limit 1');
		if ($exists->fetch_column()) {
		?>
			<p>new <code>tagusage</code> view exists.</p>
		<?php
			self::Done();
		} else
			self::CreateTagUsageView();
	}

	private static function UserSetupLink(): void {
		?>
		<p><a href=users.php>user migration</a> is not far enough along to start photo migration.</p>
	<?php
	}

	private static function CreateSubsiteTable(): void {
		$file = file_get_contents('../../etc/db/tables/subsite.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreateSubsiteRow(): void {
		self::$db->real_query('insert into subsite (id, name, calltoaction, verb) values (\'album\', \'photo album\', \'see my photos\', \'photoed\')');
	?>
		<p>created photo album row in new <code>subsite</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreatePostTable(): void {
		$file = file_get_contents('../../etc/db/tables/post.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotosToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(op.posted), op.caption, \'album\', concat(\'/album/\', op.url), 1, concat(\'<p><img class=photo src="/album/photos/\', op.url, \'.jpeg"></p>\'), true from photos as op left join post on post.subsite=\'album\' and post.url=concat(\'/album/\', op.url) where post.id is null');
	?>
		<p>copied photos into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreatePhotoTable(): void {
		$file = file_get_contents('../../etc/db/tables/photo.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>photo</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotosToPhoto(): void {
		self::$db->real_query('insert into photo (id, post, youtube, taken, year, story, storymd) select ph.url, ps.id, ph.youtube, from_unixtime(ph.taken), ph.year, ph.story, ph.storymd from photos as ph left join post as ps on ps.url=concat(\'/album/\', ph.url) left join photo on photo.id=ph.url where photo.id is null');
	?>
		<p>copied photos into new <code>photo</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreateTagTable(): void {
		$file = file_get_contents('../../etc/db/tables/tag.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotoTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select pt.name, \'album\', pt.description from photos_tags as pt left join tag on tag.name=pt.name where tag.name is null');
	?>
		<p>copied photo tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreatePostTagTable(): void {
		$file = file_get_contents('../../etc/db/tables/post_tag.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyPhotoPostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select ph.post, pt.name from photos_taglinks as pl left join photos as op on op.id=pl.photo left join photo as ph on ph.id=op.url left join photos_tags as pt on pt.id=pl.tag left join post_tag npt on npt.post=ph.post and npt.tag=pt.name where npt.post is null');
	?>
		<p>copied photo tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CreateTagUsageView(): void {
		$file = file_get_contents('../../etc/db/views/tagusage.sql');
		self::$db->real_query($file);
	?>
		<p>created <code>tagusage</code> view. refresh the page to take the next step.</p>
	<?php
	}

	private static function Done(): void {
	?>
		<p>done migrating photos, at least for now!</p>
<?php
	}
}
new PhotoTransition();
