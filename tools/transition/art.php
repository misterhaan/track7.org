<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class ArtTransition extends TransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('art', 'visual art', 'see sketches and digital artwork', 'arted');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art\' limit 1');
		if ($exists->fetch_column()) {
			$hasNewColumn = self::$db->query('show columns from art like \'post\'');
			if ($hasNewColumn->num_rows) {
?>
				<p>new <code>art</code> table exists.</p>
				<?php
				self::CheckArtRows();
			} else {
				$missing = self::$db->query('select 1 from art left join post on post.subsite=\'art\' and post.url=concat(\'/art/\', art.url) where post.id is null limit 1');
				if ($missing->fetch_column())
					self::CopyArtToPost();
				else {
				?>
					<p>all old art exist in new <code>post</code> table.</p>
				<?php
					self::CheckTagTable();
				}
			}
		} else
			self::CreateTable('art');
	}

	protected static function CheckTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_tags\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from art_tags as ot left join tag as t on t.name=ot.name and t.subsite=\'art\' where t.name is null limit 1');
			if ($missing->fetch_column())
				self::CopyArtTags();
			else {
				?>
				<p>all old art tags exist in new <code>tag</code> table.</p>
			<?php
				self::CheckPostTagTable();
			}
		} else {
			?>
			<p>old art tags table no longer exists.</p>
			<?php
			self::CheckArtTable();
		}
	}

	protected static function CheckPostTagRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_taglinks\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from art_taglinks as atl left join art as oa on oa.id=atl.art left join post as p on p.url=concat(\'/art/\', oa.url) left join art_tags as oat on oat.id=atl.tag left join post_tag as npt on npt.post=p.id and npt.tag=oat.name where npt.post is null limit 1');
			if ($missing->fetch_column())
				self::CopyArtPostTags();
			else {
			?>
				<p>all old art tagging exists in new <code>post_tag</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old art tagging table no longer exists.</p>
			<?php
			self::CheckOldTags();
		}
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from art_comments as ac left join art as oa on oa.id=ac.art left join post as p on p.url=concat(\'/art/\', oa.url) left join comment as c on c.post=p.id and c.instant=from_unixtime(ac.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyArtComments();
			else {
			?>
				<p>all old art comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckVoteTable();
			}
		} else {
			?>
			<p>old art comments table no longer exists.</p>
			<?php
			self::CheckOldTagLinks();
		}
	}

	protected static function CheckVoteRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_votes\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from art_votes as av left join art as oa on oa.id=av.art left join post as p on p.url=concat(\'/art/\', oa.url) left join vote as v on v.post=p.id and (av.voter>0 and v.user=av.voter or av.ip>0 and v.ip=av.ip) where v.vote is null limit 1');
			if ($missing->fetch_column())
				self::CopyArtVotes();
			else {
			?>
				<p>all old art votes exists in new <code>vote</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old art tags table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'art_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old art comment triggers no longer exist.</p>
		<?php
			self::CheckArtTriggers();
		}
	}

	private static function CheckArtTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'art\' limit 1');
		if ($exists->fetch_column())
			self::DeleteArtTriggers();
		else {
		?>
			<p>old art triggers no longer exist.</p>
		<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from contributions where srctbl=\'art\' or srctbl=\'art_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteContributions();
		else {
		?>
			<p>art contributions no longer exist.</p>
		<?php
			self::CheckOldVotes();
		}
	}

	private static function CheckOldVotes(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_votes\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldVotes();
		else {
		?>
			<p>old art votes table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckOldComments(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old art comments table no longer exists.</p>
		<?php
			self::CheckOldTagLinks();
		}
	}

	private static function CheckOldTagLinks(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_taglinks\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTagLinks();
		else {
		?>
			<p>old art tagging table no longer exists.</p>
		<?php
			self::CheckOldTags();
		}
	}

	private static function CheckOldTags(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art_tags\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldTags();
		else {
		?>
			<p>old art tags table no longer exists.</p>
			<?php
			self::CheckArtTable();
		}
	}

	private static function CheckArtTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'art\' limit 1');
		if ($exists->fetch_column()) {
			$hasNewColumn = self::$db->query('show columns from art like \'post\'');
			if ($hasNewColumn->num_rows) {
			?>
				<p>new <code>art</code> table exists.</p>
			<?php
				self::CheckArtRows();
			} else
				self::RenameArtTable();
		} else
			self::CreateTable('art');
	}

	private static function CheckArtRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'oldart\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from oldart as oa left join art as a on a.id=oa.url where a.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyArt();
			else {
			?>
				<p>all old art exist in new <code>art</code> table.</p>
			<?php
				self::CheckOldArtTable();
			}
		} else {
			?>
			<p>old art table no longer exists.</p>
		<?php
			self::CheckImageFormatTable();
		}
	}

	private static function CheckOldArtTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'oldart\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldArtTable();
		else {
		?>
			<p>old art table no longer exists.</p>
		<?php
			self::CheckImageFormatTable();
		}
	}

	private static function CheckImageFormatTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'image_formats\' limit 1');
		if ($exists->fetch_column())
			self::DeleteImageFormatTable();
		else {
		?>
			<p>old image format table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyArtToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(oa.posted), oa.title, \'art\', concat(\'/art/\', oa.url), 1, concat(\'<p><img class=art src="/art/img/\', oa.url, \'.\', f.ext , \'"></p>\'), true from art as oa left join image_formats as f on f.id=oa.format left join post on post.subsite=\'art\' and post.url=concat(\'/art/\', oa.url) where post.id is null');
		?>
		<p>copied art into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyArtTags(): void {
		self::$db->real_query('insert into tag (name, subsite, description) select ot.name, \'art\', ot.description from art_tags as ot left join tag on tag.name=ot.name and tag.subsite=\'art\' where tag.name is null');
	?>
		<p>copied art tags into new <code>tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyArtPostTags(): void {
		self::$db->real_query('insert into post_tag (post, tag) select p.id, oat.name from art_taglinks as atl left join art as oa on oa.id=atl.art join post as p on p.url=concat(\'/art/\', oa.url) left join art_tags as oat on oat.id=atl.tag left join post_tag as npt on npt.post=p.id and npt.tag=oat.name where npt.post is null');
	?>
		<p>copied art tagging into new <code>post_tag</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyArtComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(ac.posted), p.id, ac.user, ac.name, ac.contacturl, ac.html, ac.markdown from art_comments as ac left join art as oa on oa.id=ac.art left join post as p on p.url=concat(\'/art/\', oa.url) left join comment as c on c.post=p.id and c.instant=from_unixtime(ac.posted) where c.id is null');
	?>
		<p>copied art comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyArtVotes(): void {
		self::$db->query('insert into vote (post, user, ip, instant, vote) select p.id, av.voter, av.ip, from_unixtime(av.posted), av.vote from art_votes as av left join art as oa on oa.id=av.art left join post as p on p.url=concat(\'/art/\', oa.url) left join vote as v on v.post=p.id and (av.voter>0 and v.user=av.voter or av.ip>0 and v.ip=av.ip) where v.vote is null');
	?>
		<p>copied art votes into new <code>vote</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists art_comment_added');
		self::$db->real_query('drop trigger if exists art_comment_changed');
		self::$db->real_query('drop trigger if exists art_comment_deleted');
	?>
		<p>deleted old art comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteArtTriggers(): void {
		self::$db->real_query('drop trigger if exists art_added');
		self::$db->real_query('drop trigger if exists art_changed');
	?>
		<p>deleted old art triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'art\' or srctbl=\'art_comments\'');
	?>
		<p>deleted old art contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldVotes(): void {
		self::$db->real_query('drop table art_votes');
	?>
		<p>deleted old art votes table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table art_comments');
	?>
		<p>deleted old art comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTagLinks(): void {
		self::$db->real_query('drop table art_taglinks');
	?>
		<p>deleted old art tagging table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldTags(): void {
		self::$db->real_query('drop table art_tags');
	?>
		<p>deleted old art tags table. refresh the page to take the next step.</p>
	<?php
	}

	private static function RenameArtTable(): void {
		self::$db->real_query('rename table art to oldart');
	?>
		<p>renamed old art table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyArt(): void {
		self::$db->real_query('insert into art (id, post, ext, deviation, html, markdown) select oa.url, p.id, f.ext, oa.deviation, oa.deschtml, oa.descmd from oldart as oa left join post as p on p.url=concat(\'/art/\', oa.url) left join image_formats as f on f.id=oa.format left join art as a on a.id=oa.url where a.id is null');
	?>
		<p>copied art into new <code>art</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldArtTable(): void {
		self::$db->real_query('drop table oldart');
	?>
		<p>deleted old art table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteImageFormatTable(): void {
		self::$db->real_query('drop table image_formats');
	?>
		<p>deleted old image format table. refresh the page to take the next step.</p>
	<?php
	}

	private static function Done(): void {
	?>
		<p>done migrating art, at least for now!</p>
<?php
	}
}
new ArtTransition();
