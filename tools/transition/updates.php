<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/transitionPage.php';

class UpdatesTransition extends TransitionPage {
	public function __construct() {
		self::$subsite = new Subsite('updates', '', '', 'updated');
		parent::__construct();
	}

	protected static function CheckPostRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'update_messages\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from update_messages left join post on post.subsite=\'updates\' and post.instant=from_unixtime(update_messages.posted) where post.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyUpdatesToPost();
			else {
?>
				<p>all old updates exist in new <code>post</code> table.</p>
			<?php
				self::CheckTagUsageView();
			}
		} else {
			?>
			<p>old updates table no longer exists.</p>
			<?php
			self::Done();
		}
	}

	protected static function CheckTagRows(): void {
		throw new DetailedException('CheckTagRows() not implemented because updates don’t have tags');
	}

	protected static function CheckPostTagRows(): void {
		throw new DetailedException('CheckPostTagRows() not implemented because updates don’t have tags');
	}

	protected static function CheckCommentRows(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'update_comments\' limit 1');
		if ($exists->fetch_column()) {
			$missing = self::$db->query('select 1 from update_comments as uc left join update_messages as ou on ou.id=uc.message left join post as p on p.subsite=\'updates\' and p.instant=from_unixtime(ou.posted) left join comment as c on c.post=p.id and c.instant=from_unixtime(uc.posted) where c.id is null limit 1');
			if ($missing->fetch_column())
				self::CopyUpdateComments();
			else {
			?>
				<p>all old update comments exist in new <code>comment</code> table.</p>
			<?php
				self::CheckCommentTriggers();
			}
		} else {
			?>
			<p>old update comment table no longer exists.</p>
		<?php
			self::CheckContributions();
		}
	}

	private static function CheckCommentTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'update_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteCommentTriggers();
		else {
		?>
			<p>old update comment triggers no longer exist.</p>
		<?php
			self::CheckUpdateTriggers();
		}
	}

	private static function CheckUpdateTriggers(): void {
		$exists = self::$db->query('select 1 from information_schema.triggers where trigger_schema=\'track7\' and event_object_table=\'update_messages\' limit 1');
		if ($exists->fetch_column())
			self::DeleteUpdateTriggers();
		else {
		?>
			<p>old update triggers no longer exist.</p>
			<?php
			self::CheckContributions();
		}
	}

	private static function CheckContributions(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists = self::$db->query('select 1 from contributions where srctbl=\'update_messages\' or srctbl=\'update_comments\' limit 1');
			if ($exists->fetch_column())
				self::DeleteContributions();
			else {
			?>
				<p>update contributions no longer exist.</p>
			<?php
				self::CheckContributionTable();
			}
		} else {
			?>
			<p>old contributions table no longer exists.</p>
			<?php
			self::CheckOldComments();
		}
	}

	private static function CheckContributionTable(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'contributions\' limit 1');
		if ($exists->fetch_column()) {
			$exists =			self::$db->query('select 1 from contributions limit 1');
			if ($exists->fetch_column()) {
			?>
				<p>old contributions table still has data — not dropping.</p>
			<?php
				self::CheckOldComments();
			} else
				self::DropContributions();
		} else {
			?>
			<p>old contributions table no longer exists.</p>
		<?php
			self::CheckOldComments();
		}
	}

	private static function CheckOldComments(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'update_comments\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldComments();
		else {
		?>
			<p>old update comment table no longer exists.</p>
		<?php
			self::CheckOldUpdates();
		}
	}

	private static function CheckOldUpdates(): void {
		$exists = self::$db->query('select 1 from information_schema.tables where table_schema=\'track7\' and table_name=\'update_messages\' limit 1');
		if ($exists->fetch_column())
			self::DeleteOldUpdates();
		else {
		?>
			<p>old updates table no longer exists.</p>
		<?php
			self::Done();
		}
	}

	private static function CopyUpdatesToPost(): void {
		self::$db->real_query('insert into post (instant, title, subsite, url, author, preview, hasmore) select from_unixtime(u.posted), \'track7 update\', \'updates\', concat(\'/updates/\', u.id), 1, u.html, false from update_messages as u left join post on post.subsite=\'update\' and post.instant=from_unixtime(u.posted) where post.id is null');
		self::$db->real_query('update post set url=concat(\'/updates/\', id) where subsite=\'updates\'');
		?>
		<p>copied updates into new <code>post</code> table. refresh the page to take the next step.</p>
	<?php
	}

	private static function CopyUpdateComments(): void {
		throw new DetailedException('CopyUpdateComments() not yet implemented (nobody commented on an update so far!)');
	}

	private static function DeleteCommentTriggers(): void {
		self::$db->real_query('drop trigger if exists update_comment_added');
		self::$db->real_query('drop trigger if exists update_comment_changed');
		self::$db->real_query('drop trigger if exists update_comment_deleted');
	?>
		<p>deleted old update comment triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteUpdateTriggers(): void {
		self::$db->real_query('drop trigger if exists update_message_added');
		self::$db->real_query('drop trigger if exists update_message_changed');
	?>
		<p>deleted old update triggers. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteContributions(): void {
		self::$db->real_query('delete from contributions where srctbl=\'update_messages\' or srctbl=\'update_comments\'');
	?>
		<p>deleted old update contributions. refresh the page to take the next step.</p>
	<?php
	}

	private static function DropContributions(): void {
		self::$db->real_query('drop table contributions');
	?>
		<p>dropped old contributions table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldComments(): void {
		self::$db->real_query('drop table update_comments');
	?>
		<p>deleted old update comments table. refresh the page to take the next step.</p>
	<?php
	}

	private static function DeleteOldUpdates(): void {
		self::$db->real_query('drop table update_messages');
	?>
		<p>deleted old updates table. refresh the page to take the next step.</p>
	<?php
	}

	private static function Done(): void {
	?>
		<p>done migrating updates, at least for now!</p>
<?php
	}
}
new UpdatesTransition();
