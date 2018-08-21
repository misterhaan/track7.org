<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for comments api requests.
 * @author misterhaan
 */
class commentsApi extends t7api {
	const MAXCOMMENTS = 24;

	private static $AllowedTypes = ['blog', 'guide', 'photos', 'art', 'lego', 'stories', 'code_vs', 'code_web', 'update'];

	/**
	 * write out the documentation for the comments api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
		?>
			<p>
				any type parameter in the comments api must be in this list:
				<?php echo implode(', ', self::$AllowedTypes); ?>
			</p>

			<h2 id=postadd>post add</h2>
			<p>adds a new comment.</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of comment.  required.</dd>
				<dt>key</dt>
				<dd>id of the thing being commented on.  required.</dd>
				<dt>md</dt>
				<dd>the new comment in markdown.  required.</dd>
				<dt>name</dt>
				<dd>
					comment author name.  ignored if logged in (then we have a username).
					if blank, default anonymous name is used.
				</dd>
				<dt>contact</dt>
				<dd>
					comment author contact url.  ignored if logged in (then we link to
					the track7 profile).  if blank, author name is displayed without a
					link.
				</dd>
			</dl>

			<h2 id=postdelete>post delete</h2>
			<p>delete a comment.</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of comment.  required.</dd>
				<dt>id</dt>
				<dd>id of comment.  required.</dd>
			</dl>

			<h2 id=getkeyed>get keyed</h2>
			<p>get the latest comments for the specified type and key.</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of comment.  required.</dd>
				<dt>key</dt>
				<dd>id of the thing we’re displaying comments for.  required.</dd>
				<dt>oldest</dt>
				<dd>
					only get comments older than this timestamp.  optional; default gets
					newest comments.
				</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>save new comment content.</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of comment.  required.</dd>
				<dt>id</dt>
				<dd>id of comment.  required.</dd><dd>
				<dt>markdown</dt>
				<dd>new comment content in markdown format.  required.</dd>
			</dl>

<?php
	}

	/**
	 * adds a new comment
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function addAction($ajax) {
		global $db, $user;
		if(isset($_POST['type']) && self::IsTypeSupported($_POST['type']))
			if(isset($_POST['key']) && $_POST['key'] && isset($_POST['md']) && $_POST['md'] && !ctype_space($_POST['md']))
				if($user->IsLoggedIn() || isset($_POST['name'])) {
					$ajax->Data->html = t7format::Markdown($_POST['md']);
					$ajax->Data->posted = +time();
					if(!$user->IsLoggedIn()) {
						$ajax->Data->name = trim($_POST['name']);
						if($ajax->Data->name == '')
							$ajax->Data->name = $user->DisplayName;  // grab the default display name for non-logged-in users
							$ajax->Data->contacturl = t7format::Link($_POST['contact']);
							$ajax->Data->canchange = false;
							$ajax->Data->friend = false;
							$ajax->Data->username = $ajax->Data->displayname = $ajax->Data->avatar = $ajax->Data->level = null;
					}
					$ins = $user->IsLoggedIn()
						? 'insert into ' . $_POST['type'] . '_comments (' . self::KeyName($_POST['type']) . ', posted, user, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $user->ID . '\', \'' . $db->escape_string($ajax->Data->html) . '\', \'' . $db->escape_string($_POST['md']) . '\')'
						: 'insert into ' . $_POST['type'] . '_comments (' . self::KeyName($_POST['type']) . ', posted, name, contacturl, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $db->escape_string($ajax->Data->name) . '\', \'' . $db->escape_string($ajax->Data->contacturl) . '\', \'' . $db->escape_string($ajax->Data->html) . '\', \'' . $db->escape_string($_POST['md']) . '\')';
					if($db->real_query($ins)) {
						$ajax->Data->id = $db->insert_id;
						if($act = $db->query('select title, url from contributions where srctbl=\'' . $_POST['type'] . '_comments\' and id=\'' . +$ajax->Data->id . '\''))
							if($act = $act->fetch_object())
								t7send::Tweet('comment on ' . $act->title, t7format::FullUrl($act->url));
						$ajax->Data->posted = t7format::TimeTag(t7format::DATE_LONG, $ajax->Data->posted);
						if($user->IsLoggedIn()) {
							$db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$user->ID . '\' group by author) where id=\'' . +$user->ID . '\'');
							$ajax->Data->canchange = true;
							$ajax->Data->username = $user->Username;
							$ajax->Data->displayname = $user->DisplayName;
							$ajax->Data->friend = false;
							$ajax->Data->avatar = $user->Avatar;
							$ajax->Data->level = $user->GetLevelName();
							$ajax->Data->name = '';
							$ajax->Data->contacturl = '';
						}
					} else
						$ajax->Fail('error saving comment', $db->errno . ' ' . $db->error);
				} else
					$ajax->Fail('you’re not signed in but we didn’t ask your name.  you might need to sign in again.');
			else
				$ajax->Fail('key and md are required.');
		else
			$ajax->Fail('type needs to be a value from this list:  ' . implode(', ', self::$AllowedTypes));
	}

	/**
	 * delete a comment.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function deleteAction($ajax) {
		global $db, $user;
		if(isset($_POST['type']) && self::IsTypeSupported($_POST['type']))
			if(isset($_POST['id']) && +$_POST['id'])
				if($user->IsLoggedIn())
					if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
						if($comment = $comment->fetch_object())
							if($user->ID == $comment->user || $user->IsAdmin())
								if($db->real_query('delete from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1')) {
									if($comment->user)
										$db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$comment->user . '\' group by author) where id=\'' . +$comment->user . '\'');
								} else
									$ajax->Fail('error deleting comment.');
							else
								$ajax->Fail('you can only delete comments you posted.');
						else
							$ajax->Fail('comment not found.');
					else
						$ajax->Fail('error looking up comment', $db->errno . ' ' . $db->error);
				else
					$ajax->Fail('you must be signed in to delete your comment.  you were probably signed out for inactivity.');
			else
				$ajax->Fail('id is required.');
		else
			$ajax->Fail('type needs to be a value from this list:  ' . implode(', ', self::$AllowedTypes));
	}

	/**
	 * get all the comments for a specific thing based on type and key.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function keyedAction($ajax) {
		global $db, $user;
		if(isset($_GET['type']) && self::IsTypeSupported($_GET['type']))
			if(isset($_GET['key']) && $_GET['key']) {
				$oldest = isset($_GET['oldest']) && $_GET['oldest'] ? +$_GET['oldest'] : false;
				$comments = '(select c.id, c.posted, c.user as canchange, u.username, u.displayname, u.avatar, u.level, f.fan as friend, c.name, c.contacturl, c.markdown, c.html from ' . $_GET['type'] . '_comments as c left join users as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=\'' . +$user->ID . '\' where c.' . self::KeyName($_GET['type']) . '=\'' . $db->escape_string($_GET['key']) . ($oldest ? '\' and c.posted<\'' . $oldest : '') . '\' order by c.posted desc limit ' . self::MAXCOMMENTS . ') order by posted';
				if($comments = $db->query($comments)) {
					$ajax->Data->comments = [];
					$ajax->Data->oldest = 0;
					while($comment = $comments->fetch_object()) {
						$comment->id += 0;
						$ajax->Data->oldest = +$comment->posted;
						$comment->posted = t7format::TimeTag(t7format::DATE_LONG, $comment->posted);
						if(!$user->IsLoggedIn() && substr($comment->contacturl, 0, 7) == 'mailto:')
							$comment->contacturl = '';
						$comment->canchange = $user->IsLoggedIn() && ($comment->canchange == $user->ID && $comment->markdown || $user->IsAdmin());
						if(!$comment->canchange)
							unset($comment->markdown);
						elseif(!$comment->markdown && $user->IsLoggedIn())
							$comment->markdown = $comment->html;
						if($comment->canchange)
							$comment->editing = false;
						if($comment->level)
							$comment->level = t7user::LevelNameFromNumber($comment->level);
						if($comment->avatar === '')
							$comment->avatar = t7user::DEFAULT_AVATAR;
						$ajax->Data->comments[] = $comment;
					}
					if($more = $db->query('select count(1) as num from ' . $_GET['type'] . '_comments where ' . self::KeyName($_GET['type']) . '=\'' . $db->escape_string($_GET['key']) . '\' and posted<\'' . +$ajax->Data->oldest . '\''))
						$ajax->Data->more = $more->num_rows > 0;
				} else
					$ajax->Fail('error looking up comments' , $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('key is required.');
		else
			$ajax->Fail('type needs to be a value from this list:  ' . implode(', ', self::$AllowedTypes));
	}

	/**
	 * update the content of a comment.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if(isset($_POST['type']) && self::IsTypeSupported($_POST['type']))
			if(isset($_POST['id']) && +$_POST['id'] && isset($_POST['markdown']) && trim($_POST['markdown']))
				if($user->IsLoggedIn())
					if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
						if($comment = $comment->fetch_object())
							if($user->ID == $comment->user || $user->IsAdmin()) {
								$ajax->Data->html = t7format::Markdown($_POST['markdown']);
								if(!$db->real_query('update ' . $_POST['type'] . '_comments set markdown=\'' . $db->escape_string($_POST['markdown']) . '\', html=\'' . $db->escape_string($ajax->Data->html) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
									$ajax->Fail('error updating comment', $db->errno . ' ' . $db->error);
							} else
								$ajax->Fail('you can only edit comments you posted.');
						else
							$ajax->Fail('comment not found.');
					else
						$ajax->Fail('error looking up comment', $db->errno . ' ' . $db->error);
				else
					$ajax->Fail('you must be signed in to edit your comment.  you were probably signed out for inactivity.');
			else
				$ajax->Fail('id and markdown are required.');
		else
			$ajax->Fail('type needs to be a value from this list:  ' . implode(', ', self::$AllowedTypes));
	}

	/**
	 * check if the type specified is an actual comment type.
	 * @param string $type comment type to check.
	 * @return boolean whether the comment type is supported.
	 */
	private static function IsTypeSupported($type) {
		return in_array($type, self::$AllowedTypes);
	}

	/**
	 * get the name of the key column for the comment type specified.
	 * @param string $type comment type to look up.
	 * @return string key column name.
	 */
	private static function KeyName($type) {
		switch($type) {
			case 'blog':
				return 'entry';
			case 'photos':
				return 'photo';
			case 'stories':
				return 'story';
			case 'code_vs':
				return 'application';
			case 'code_web':
				return 'script';
			case 'update':
				return 'message';
			default:
				return $type;
		}
	}
}
commentsApi::Respond();
