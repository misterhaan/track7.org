<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for forum api requests.
 * @author misterhaan
 */
class forumApi extends t7api {
	const MAX_THREADS = 24;

	/**
	 * write out the documentation for the gameworlds api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=postdelete>post delete</h2>
			<p>
				deletes a reply from a discussion.  also deletes the discussion if it
				only had one reply.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>id of the reply to delete</dd>
			</dl>

			<h2 id=getdiscussion>get discussion</h2>
			<p>retrieves a discussion with all of its replies.</p>
			<dl class=parameters>
				<dt>discussion</dt>
				<dd>id of the discussion to look up.</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>
				retrieves the lastest forum discussions with most recently replied
				first.
			</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve discussions with that tag.</dd>
				<dt>before</dt>
				<dd>specify a timestamp to only return entries before then.</dd>
			</dl>

			<h2 id=getreplies>get replies</h2>
			<p>
				get replies newest to oldest without regard for which discussion they
				belong to.
			</p>
			<dl class=parameters>
				<dt>before</dt>
				<dd>if specified, only get replies older than this timestamp.</dd>
				<dt>userid</dt>
				<dd>if specified, only get replies posted by this user.</dd>
			</dl>

			<h2 id=postreply>post reply</h2>
			<p>
				saves a new reply to the discussion.
			</p>
			<dl class=parameters>
				<dt>discussion</dt>
				<dd>id of discussion being replied to.  required.</dd>
				<dt>markdown</dt>
				<dd>reply content in markdown format.  required.</dd>
				<dt>authorname</dt>
				<dd>
					name of the reply’s author.  ignored if reply comes from a logged-in
					user.  anonymous if blank or missing and not logged in.
				</dd>
				<dt>authorcontact</dt>
				<dd>
					contact url for the reply’s author.  ignored if reply comes from a
					logged-in user.  author name displayed without a link if blank or
					missing.
				</dd>
			</dl>

			<h2 id=getreplyid>get replyid</h2>
			<p>
				retrieves a reply id from the old post id.  used to translate links from
				the previous database.
			</p>
			<dl class=parameters>
				<dt>postid</dt>
				<dd>id of the post to translate into a reply id.</dd>
			</dl>

			<h2 id=poststart>post start</h2>
			<p>
				start a new discussion.
			</p>
			<dl class=parameters>
				<dt>name</dt>
				<dd>
					name of the author.  anonymous if blank or not provided.  ignored if
					discussion started by a logged-in user.
				</dd>
				<dt>contact</dt>
				<dd>
					contact url or e-mail address of the author.  no contact link if
					blank or not provided.  ignored if discussion started by a logged-in
					user.
				</dd>
				<dt>title</dt>
				<dd>discussion title.  required.</dd>
				<dt>tags[]</dt>
				<dd>
					list of tag ids for this discussion.  untagged if empty or not
					present.
				</dd>
				<dt>markdown</dt>
				<dd>discussion content in markdown format.  required.</dd>
			</dl>

			<h2 id=postupdate>post update</h2>
			<p>
				updates an existing reply.  users may only update replies they own.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>id of the reply to update.  required.</dd>
				<dt>markdown</dt>
				<dd>reply content in markdown format.  required.</dd>
			</dl>

<?php
	}

	/**
	 * delete a reply from a discussion.  if it was the only reply, also delete
	 * the discussion.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function deleteAction($ajax) {
		global $db, $user;
		if(isset($_POST['id']) && $id = +$_POST['id'])
			if($user->IsLoggedIn()) {
				$uid = $user->ID;
				$db->autocommit(false);
				if($del = $db->prepare('delete from forum_replies where id=? and (user=? or ? in (select id from users where level=\'' . +t7user::LEVEL_ADMIN . '\'))'))
					if($del->bind_param('iii', $id, $uid, $uid))
						if($del->execute())
							if($del->affected_rows)
								if($db->query('delete from forum_discussions where id not in (select distinct discussion from forum_replies)')) {
									$ajax->Data->discussionDeleted = $db->affected_rows > 0;
									if($db->real_query('update users_stats as us set us.replies=(select count(1) from forum_replies where user=us.id)'))
										if($db->real_query('update forum_tags as t set count=(select count(1) from forum_discussion_tags where tag=t.id group by tag), lastused=(select min(r.posted) from forum_replies as r left join forum_discussion_tags as dt on dt.discussion=r.discussion where dt.tag=t.id group by dt.tag)'))
											$db->commit();
										else
											$ajax->Fail('error updating tag statistics', $db->errno . ' ' . $db->error);
									else
										$ajax->Fail('error updating user stats', $db->errno . ' ' . $db->error);
								} else
									$ajax->Fail('error deleting empty discussions', $db->errno . ' ' . $db->error);
							else
								$ajax->Fail('reply not deleted.  either it doesn’t exist or it doesn’t belong to you.');
						else
							$ajax->Fail('error executing reply deletion', $del->errno . ' ' . $del->error);
					else
						$ajax->Fail('error binding parameters to delete reply', $del->errno . ' ' . $del->error);
				else
					$ajax->Fail('error preparing to delete reply', $db->errno . ' ' . $db->error);
				if($ajax->Data->fail)
					$db->rollback();
			} else
				$ajax->Fail('must be logged in to delete a reply.');
		else
			$ajax->Fail('id is required to delete a reply.');
	}

	/**
	 * get replies for a discussion.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function discussionAction($ajax) {
		global $db, $user;
		if(isset($_GET['discussion']) && $discid = +$_GET['discussion'])
			if($replies = $db->query('select r.id, r.posted, r.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, r.name, r.contacturl, r.markdown, r.html, group_concat(concat(e.posted, \'\t\', eu.username, \'\t\', eu.displayname) order by e.posted separator \'\n\') as edits from forum_replies as r left join users as u on u.id=r.user left join users_friends as f on f.friend=r.user and f.fan=\'' . +$user->ID . '\' left join forum_edits as e on e.reply=r.id left join users as eu on eu.id=e.editor where r.discussion=\'' . +$discid . '\' group by r.id order by r.posted')) {
				$ajax->Data->replies = [];
				while($reply = $replies->fetch_object()) {
					$reply->posted = t7format::TimeTag(t7format::DATE_LONG, $reply->posted);
					if(!$user->IsLoggedIn() && substr($reply->contacturl, 0, 7) == 'mailto:')
						$reply->contacturl = '';
					$reply->canchange = $user->IsLoggedIn() && ($reply->canchange == $user->ID && $reply->markdown || $user->IsAdmin());
					if($reply->edits) {
						$edits = [];
						foreach(explode("\n", $reply->edits) as $e) {
							list($posted, $username, $display) = explode("\t", $e);
							$edits[] = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate(t7format::DATE_LONG, $posted)), 'username' => $username, 'displayname' => $display];
						}
						$reply->edits = $edits;
					} else
						$reply->edits = [];
					if(!$reply->canchange)
						unset($reply->markdown);
					elseif(!$reply->markdown && $user->IsAdmin())
						$reply->markdown = $reply->html;
					if($reply->avatar === '')
						$reply->avatar = t7user::DEFAULT_AVATAR;
					$ajax->Data->replies[] = $reply;
				}
			} else
				$ajax->Fail('database error looking up discussion detail', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('discussion is required');
	}

	/**
	 * get latest discussions.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$tagid = isset($_GET['tagid']) && +$_GET['tagid'] ? +$_GET['tagid'] : false;
		$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : time() + 43200;

		$select = 'select d.id, d.title, group_concat(t.name order by t.name) as tags, min(fr.posted) as started, min(fu.username) as startusername, min(fu.displayname) as startuserdisplay, min(fr.contacturl) as startcontact, min(fr.name) as startname, (select count(1) from forum_replies where discussion=d.id)-1 as replies, max(lr.posted) as replied, min(lu.username) as lastusername, min(lu.displayname) as lastuserdisplay, min(lr.contacturl) as lastcontact, min(lr.name) as lastname from '
			. ($tagid ? 'forum_discussion_tags as findtag left join forum_discussions as d on d.id=findtag.discussion ' : 'forum_discussions as d ')
			. 'left join forum_discussion_tags as dt on dt.discussion=d.id left join forum_tags as t on t.id=dt.tag left join forum_replies as fr on fr.discussion=d.id and fr.posted=(select min(posted) from forum_replies where discussion=d.id) left join users as fu on fu.id=fr.user left join forum_replies as lr on lr.discussion=d.id and lr.posted=(select max(posted) from forum_replies where discussion=d.id) left join users as lu on lu.id=lr.user where lr.posted<\'' . +$before . '\' '
			. ($tagid ? 'and findtag.tag=' . $tagid . ' ' : '')
			. 'group by d.id order by max(lr.posted) desc limit ' . self::MAX_THREADS;

		if($threads = $db->query($select)) {
			$ajax->Data->threads = [];
			$ajax->Data->latest = 0;
			while($thread = $threads->fetch_object()) {
				$ajax->Data->latest = +$thread->replied;
				$thread->started = t7format::TimeTag('ago', $thread->started, t7format::DATE_LONG);
				$thread->tags = explode(',', $thread->tags);
				$thread->replies = +$thread->replies;
				$thread->repliesText = $thread->replies > 1 ? $thread->replies . ' replies' : ($thread->replies ? '1 reply' : 'no replies');
				$thread->replied = t7format::TimeTag('ago', $thread->replied, t7format::DATE_LONG);
				$ajax->Data->threads[] = $thread;
			}
			$ajax->Data->more = false;
			$chk = 'select 1 from forum_discussions as d left join forum_replies as lr on lr.discussion=d.id and lr.posted=(select max(posted) from forum_replies where discussion=d.id) where lr.posted<\'' . $ajax->Data->latest . '\'';
			if($tagid)
				$chk .= ' and \'' . $tagid . '\' in (select tag from forum_discussion_tags where discussion=d.id) limit 1';
				if($chk = $db->query($chk))
					$ajax->Data->more = $chk->num_rows > 0;
		} else
			$ajax->Fail('error getting latest discussions', $db->errno . ' ' . $db->error);
	}

	/**
	 * get a list of replies regardless of discussion.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function repliesAction($ajax) {
		global $db, $user;
		$before = isset($_GET['before']) && $_GET['before'] ? +$_GET['before'] : time() + 43200;
		$userid = isset($_GET['userid']) && $_GET['userid'] ? +$_GET['userid'] : 0;
		if($rs = $db->query('select r.discussion, d.title, r.id, r.posted, r.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, r.name, r.contacturl, r.markdown, r.html, group_concat(concat(e.posted, \'\t\', eu.username, \'\t\', eu.displayname) order by e.posted separator \'\n\') as edits from forum_replies as r left join forum_discussions as d on d.id=r.discussion left join users as u on u.id=r.user left join users_friends as f on f.friend=r.user and f.fan=\'' . +$user->ID . '\' left join forum_edits as e on e.reply=r.id left join users as eu on eu.id=e.editor where r.posted<\'' . $before . ($userid ? '\' and r.user=\'' . $userid : '') . '\' group by r.id order by r.posted desc limit ' . self::MAX_THREADS)) {
			$ajax->Data->replies = [];
			$ajax->Data->latest = 0;
			while($r = $rs->fetch_object()) {
				$ajax->Data->latest = $r->posted;
				$r->posted = t7format::TimeTag(t7format::DATE_LONG, $r->posted);
				if(!$user->IsLoggedIn() && substr($r->contacturl, 0, 7) == 'mailto:')
					$r->contacturl = '';
				$r->canchange = $user->IsLoggedIn() && ($r->canchange == $user->ID && $r->markdown || $user->IsAdmin());
				if($r->edits) {
					$edits = [];
					foreach(explode("\n", $r->edits) as $e) {
						list($posted, $username, $display) = explode("\t", $e);
						$edits[] = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate(t7format::DATE_LONG, $posted)), 'username' => $username, 'displayname' => $display];
					}
					$r->edits = $edits;
				}
				if(!$r->canchange)
					unset($r->markdown);
				elseif(!$r->markdown && $user->IsAdmin())
					$r->markdown = $r->html;
				if($r->avatar === '')
					$r->avatar = t7user::DEFAULT_AVATAR;
				$ajax->Data->replies[] = $r;
			}
		} else
			$ajax->Fail('error looking up replies', $db->errno . ' ' . $db->error);
	}

	/**
	 * add a new reply to a discussion.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function replyAction($ajax) {
		global $db, $user;
		if(isset($_POST['markdown']) && ($markdown = trim($_POST['markdown'])) && isset($_POST['discussion']) && ($discussion = +$_POST['discussion']))
			if($ins = $db->prepare('insert into forum_replies (discussion, posted, user, name, contacturl, html, markdown) values (?, ?, ?, ?, ?, ?, ?)')) {
				$posted = time();
				$userid = $user->IsLoggedIn() ? $user->ID : null;
				$name = $user->IsLoggedIn() ? '' : (isset($_POST['authorname']) && trim($_POST['authorname']) ? trim($_POST['authorname']) : 'random internet person');
				$contact = $user->IsLoggedIn() || !isset($_POST['authorcontact']) ? '' : t7format::Link(trim($_POST['authorcontact']));
				$html = t7format::Markdown($markdown);
				if($ins->bind_param('iiissss', $discussion, $posted, $userid, $name, $contact, $html, $markdown))
					if($ins->execute()) {
						$id = +$ins->insert_id;
						$ajax->Data->reply = [
							'id' => $id,
							'posted' => t7format::TimeTag(t7format::DATE_LONG, $posted),
							'canchange' => $user->IsLoggedIn(),
							'username' => $user->IsLoggedIn() ? $user->Username : null,
							'displayname' => $user->IsLoggedIn() ? $user->DisplayName : null,
							'avatar' => $user->IsLoggedIn() ? $user->Avatar : null,
							'level' => $user->IsLoggedIn() ? $user->GetLevelName() : null,
							'friend' => null,
							'name' => $name,
							'contacturl' => $contact,
							'markdown' => $markdown,
							'html' => $html
						];
						if($user->IsLoggedIn())
							$db->real_query('update users_stats as u set u.replies=(select count(1) from forum_replies where user=u.id) where u.id=\'' . +$user->ID . '\'');
						if($title = $db->query('select d.title from forum_replies as r left join forum_discussions as d on d.id=r.discussion where r.id=' . $id))
							if($title = $title->fetch_object())
								t7send::Tweet(($user->IsLoggedIn() ? $user->DisplayName : $name) . ' discussed ' . $title->title, t7format::FullUrl('/forum/' . $discussion . '#r' . $id));
					} else
						$ajax->Fail('error executing query to add reply', $ins->errno . ' ' . $ins->error);
				else
					$ajax->Fail('error binding parameters to add reply', $ins->errno . ' ' . $ins->error);
			} else
				$ajax->Fail('error preparing to add reply', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('discussion and markdown are required.');
	}

	/**
	 * get reply id from a post id.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function replyidAction($ajax) {
		global $db;
		if(isset($_GET['postid']))
			if($reply = $db->query('select id from forum_replies where postid=\'' . +$_GET['postid'] . '\' limit 1'))
				if($reply = $reply->fetch_object())
					$ajax->Data->id = $reply->id;
				else
					$ajax->Fail('no reply with post id ' . +$_GET['postid']);
			else
				$ajax->Fail('error looking up reply from postid', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('postid is required.');
	}

	/**
	 * start a new discussion.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function startAction($ajax) {
		global $db, $user;
		if(isset($_POST['title']) && ($title = trim($_POST['title'])) && isset($_POST['tags']) && is_array($_POST['tags']) && count($_POST['tags']) && isset($_POST['message']) && ($markdown = trim($_POST['message'])))
			if($user->IsLoggedIn() || isset($_POST['name']) && isset($_POST['contact'])) {
				$userid = $user->IsLoggedIn() ? +$user->ID : null;
				$name = $user->IsLoggedIn() ? '' : trim($_POST['name']) ? trim($_POST['name']) : 'random internet person';
				$contact = $user->IsLoggedIn() ? '' : trim($_POST['contact']) ? t7format::Link(trim($_POST['contact'])) : '';
				$tags = $_POST['tags'];
				$html = t7format::Markdown($markdown);
				$posted = +time();
				$db->begin_transaction();
				if($ins = $db->prepare('insert into forum_discussions (title) values (?)'))
					if($ins->bind_param('s', $title))
						if($ins->execute()) {
							$discussion = $ins->insert_id;
							$ins->close();
							if($ins = $db->prepare('insert into forum_replies (discussion, posted, user, name, contacturl, html, markdown) values (?, ?, ?, ?, ?, ?, ?)'))
								if($ins->bind_param('iiissss', $discussion, $posted, $userid, $name, $contact, $html, $markdown))
									if($ins->execute()) {
										$ins->close();
										if($ins = $db->prepare('insert into forum_discussion_tags (discussion, tag) values (?, ?)'))
											if($ins->bind_param('ii', $discussion, $tag)) {
												$taglist = [];
												foreach($tags as $rawtag) {
													$tag = +$rawtag;
													$taglist[] = $tag;
													if($tag && !$ins->execute())
														$ajax->Fail('error saving tag ' . $tag, $ins->errno . '  ' . $ins->error);
												}
												$taglist = implode(',', $taglist);
												if(!$ajax->Data->fail) {
													$ins->close();
													if($db->real_query('update forum_tags set lastused=\'' . $posted . '\', count=(select count(1) from forum_discussion_tags where tag=forum_tags.id) where \',' . $db->escape_string($taglist) . ',\' like concat(\'%,\', id, \',%\')')) {
														if($user->IsLoggedIn())
															if(!$db->real_query('update users_stats as u set u.replies=(select count(1) from forum_replies where user=u.id) where u.id=\'' . +$user->ID . '\''))
																$ajax->Fail('error updating user statistics', $db->errno . ' ' . $db->error);
														if(!$ajax->Data->fail) {
															$db->commit();
															$intrans = false;
															$ajax->Data->url = '/forum/' . $discussion;
														}
													} else
														$ajax->Fail('error updating tag statistics', $db->errno . ' ' . $db->error);
												}
											} else
												$ajax->Fail('error binding tag parameters', $ins->errno . ' ' . $ins->error);
										else
											$ajax->Fail('error preparing to save tags', $db->errno . ' ' . $db->error);
									} else
										$ajax->Fail('error executing save message', $ins->errno . ' ' . $ins->error);
								else
									$ajax->Fail('error binding message parameters', $ins->errno . ' ' . $ins->error);
							else
								$ajax->Fail('error preparing to save message', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail('error executing create discussion', $ins->errno . ' ' . $ins->error);
					else
						$ajax->Fail('error binding discussion title', $ins->errno . ' ' . $ins->error);
				else
					$ajax->Fail('error preparing to create discussion', $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('oops, you’re not signed in but we didn’t ask who you are.  you might need to sign in again.');
		else
			$ajax->Fail('title, message, and at least one tag are required to start a discussion');
	}

	/**
	 * update reply.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function updateAction($ajax) {
		global $db, $user;
		if(isset($_POST['reply']) && isset($_POST['markdown']) && ($reply = +$_POST['reply']) && ($markdown = trim($_POST['markdown'])))
			if($user->IsLoggedIn()) {
				$html = t7format::Markdown($markdown);
				$uid = $user->ID;
				$db->autocommit(false);
				if($update = $db->prepare('update forum_replies set markdown=?, html=? where id=? and (user=? or ? in (select id from users where level=\'' . +t7user::LEVEL_ADMIN . '\'))'))
					if($update->bind_param('ssiii', $markdown, $html, $reply, $uid, $uid))
						if($update->execute())
							if($update->affected_rows) {
								$ajax->Data->html = $html;
								$update->close();
								if($user->IsTrusted() && isset($_POST['stealth']) && $_POST['stealth'])
									$db->commit();
								elseif($ins = $db->prepare('insert into forum_edits (reply, editor, posted) values (?, ?, ?)')) {
									$posted = time();
									if($ins->bind_param('iii', $reply, $uid, $posted))
										if($ins->execute()) {
											$ajax->Data->edit = ['datetime' => $posted, 'posted' => strtolower(t7format::LocalDate(t7format::DATE_LONG, $posted)), 'username' => $user->Username, 'displayname' => $user->DisplayName];
											$db->commit();
										} else
											$ajax->Fail('error executing edit history update', $ins->errno . ' ' . $ins->error);
									else
										$ajax->Fail('error binding parameters to update edit history', $ins->errno . ' ' . $ins->error);
								} else
									$ajax->Fail('error preparing to update edit history', $db->errno . ' ' . $db->error);
							} else
								$ajax->Fail('reply not changed.  either it’s not yours or you saved it without any changes.');
						else
							$ajax->Fail('error executing reply edit', $update->errno . ' ' . $update->error);
					else
						$ajax->Fail('error binding parameters to edit reply', $update->errno . ' ' . $update->error);
				else
					$ajax->Fail('error preparing to edit reply', $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('cannot edit reply because you are no longer logged in');
		else
			$ajax->Fail('reply and markdown are required');
	}
}
forumApi::Respond();
