<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/subsiteTransitionPage.php';

class LegoTransition extends SubsiteTransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('lego', 4, 'lego', 'lego models', 'download instructions for custom lego models', 'legoed');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		if (self::CheckTableExists('lego_models')) {
			$missing = self::$db->query('select 1 from lego_models left join post on post.subsite=\'lego\' and post.url=concat(\'/lego/\', lego_models.url) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyLegoToPost();
			else {
?>
				<p>all old lego models exist in new <code>post</code> table.</p>
			<?php
				self::CheckLegoTable();
			}
		} else {
			?>
			<p>old lego models table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CheckLegoTable(): void {
		if (self::CheckTableExists('lego')) {
		?>
			<p>new <code>lego</code> table exists.</p>
		<?php
			self::CheckLegoRows();
		} else
			self::CreateTable('lego');
	}

	private static function CheckLegoRows(): void {
		$missing = self::$db->query('select 1 from lego_models left join lego on lego.id=lego_models.url where lego.id is null limit 1');
		if ($missing->fetch_column())
			self::CopyLego();
		else {
		?>
			<p>all old lego models exist in new <code>lego</code> table.</p>
			<?php
			self::CheckTagTable();
		}
	}

	protected static function CheckTagRows(): void {
		self::CheckCommentRows();
	}

	protected static function CheckPostTagRows(): void {
		self::CheckCommentRows();
	}

	protected static function CheckCommentRows(): void {
		if (self::CheckTableExists('lego_comments')) {
			$missing = self::$db->query('select 1 from lego_comments as lc left join lego_models as ol on ol.id=lc.lego left join lego as l on l.id=ol.url left join comment as c on c.post=l.post and c.instant=from_unixtime(lc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyLegoComments();
			else {
			?>
				<p>all old lego comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckVoteTable();
			}
		} else {
			?>
			<p>old lego comment table no longer exists.</p>
			<?php
			self::CheckOldLegos();
		}
	}

	protected static function CheckVoteRows(): void {
		if (self::CheckTableExists('lego_votes')) {
			$missing = self::$db->query('select 1 from lego_votes as lv left join lego_models as ol on ol.id=lv.lego left join post as p on p.url=concat(\'/lego/\', ol.url) left join vote as v on v.post=p.id and (lv.voter>0 and v.user=lv.voter or lv.ip>0 and v.ip=lv.ip) where v.vote is null limit 1');
			if ($missing->fetch_column())
				self::CopyLegoVotes();
			else {
			?>
				<p>all old lego votes exists in new <code>vote</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old lego votes table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckCommentTriggers(): void {
		if (self::CheckTriggersExist('lego_comments'))
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old lego comment triggers no longer exist.</p>
		<?php
			self::CheckLegoTriggers();
		}
	}

	private static function CheckLegoTriggers(): void {
		if (self::CheckTriggersExist('lego_models'))
			self::DeleteLegoTriggers();
		else {
		?>
			<p>old lego triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		if (self::CheckTableExists('contributions')) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'lego_models\' or srctbl=\'lego_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>lego contributions no longer exist.</p>
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
		if (self::CheckTableExists('lego_votes'))
			self::DeleteOldVotes();
		else {
		?>
			<p>old lego votes table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckOldComments(): void {
		if (self::CheckTableExists('lego_comments'))
			self::DeleteOldComments();
		else {
		?>
			<p>old lego comments table no longer exists.</p>
		<?php
			self::CheckOldLegos();
		}
	}

	private static function CheckOldLegos(): void {
		if (self::CheckTableExists('lego_models'))
			self::DeleteOldLegos();
		else {
		?>
			<p>old legos table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyLegoToPost(): void {
		self::$db->real_query('insert into post (published, instant, title, subsite, url, author, preview, hasmore) select true, from_unixtime(l.posted), l.title, \'lego\', concat(\'/lego/\', l.url), 1, concat(\'<p><img class=lego src="/lego/data/\', l.url, \'.png"></p>\'), true from lego_models as l left join post on post.subsite=\'lego\' and post.url=concat(\'/lego/\', l.url) where post.id is null');
		?>
		<p>copied lego models into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyLego(): void {
		self::$db->real_query('insert into lego (id, post, html, markdown, pieces) select ol.url, p.id, ol.deschtml, ol.descmd, ol.pieces from lego_models as ol left join post as p on p.url=concat(\'/lego/\', ol.url) left join lego on lego.id=ol.url where lego.id is null');
	?>
		<p>copied lego models into new <code>lego</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyLegoComments(): void {
		self::$db->real_query('insert into comment (instant, post, user, name, contact, html, markdown) select from_unixtime(lc.posted), l.post, lc.user, lc.name, lc.contacturl, lc.html, lc.markdown from lego_comments as lc left join lego_models as ol on ol.id=lc.lego left join lego as l on l.id=ol.url left join comment as c on c.post=l.post and c.instant=from_unixtime(lc.posted) where c.id is null');
	?>
		<p>copied lego comments into new <code>comment</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyLegoVotes(): void {
		self::$db->query('insert into vote (post, user, ip, instant, vote) select p.id, lv.voter, lv.ip, from_unixtime(lv.posted), lv.vote from lego_votes as lv left join lego_models as ol on ol.id=lv.lego left join post as p on p.url=concat(\'/lego/\', ol.url) left join vote as v on v.post=p.id and (lv.voter>0 and v.user=lv.voter or lv.ip>0 and v.ip=lv.ip) where v.vote is null');
	?>
		<p>copied lego votes into new <code>vote</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists lego_comment_added');
		self::$db->real_query('drop trigger if exists lego_comment_changed');
		self::$db->real_query('drop trigger if exists lego_comment_deleted');
	?>
		<p>deleted old lego comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteLegoTriggers(): void {
		self::$db->real_query('drop trigger if exists lego_model_added');
		self::$db->real_query('drop trigger if exists lego_model_changed');
	?>
		<p>deleted old lego triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'lego_models\' or srctbl=\'lego_comments\'');
	?>
		<p>deleted old lego contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldVotes(): void {
		self::$db->real_query('drop table lego_votes');
	?>
		<p>deleted old lego votes table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table lego_comments');
	?>
		<p>deleted old lego comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldLegos(): void {
		self::$db->real_query('drop table lego_models');
	?>
		<p>deleted old legos table. refresh the page to take the next step.</p>
<?php
	}
}
new LegoTransition();
