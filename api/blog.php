<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for blog api requests.
 * @author misterhaan
 */
class blogApi extends t7api {
	const MAXENTRIES = 9;

	/**
	 * write out the documentation for the blog api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getcheckurl>get checkurl</h2>
			<p>check if a url is available for a blog entry.</p>
			<dl class=parameters>
				<dt>url</dt>
				<dd>url to check.</dd>
				<dt>id</dt>
				<dd>
					id of blog entry that wants to use the url.  optional; assumes new
					entry.
				</dd>
			</dl>

			<h2 id=postdelete>post delete</h2>
			<p>delete a draft blog entry.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>blog entry id to delete.</dd>
			</dl>

			<h2 id=getedit>get edit</h2>
			<p>get blog entry information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>blog entry id to load for editing.</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest blog entries with most recent first.</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve entries with that tag.</dd>
				<dt>before</dt>
				<dd>specify a timestamp to only return entries before then.</dd>
			</dl>

			<h2 id=postpublish>post publish</h2>
			<p>publish a blog entry.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>blog entry id to publish.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>save edits to a blog entry or add a new blog entry.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					blog entry id to save.  will add a new blog entry if empty or missing.
				</dd>
				<dt>title</dt>
				<dd>blog entry title as plain text.  required.</dd>
				<dt>url</dt>
				<dd>url portion specific to this blog entry.  required.</dd>
				<dt>content</dt>
				<dd>blog entry content in markdown format.  required.</dd>
				<dt>addtags</dt>
				<dd>
					list of tag names to add to the entry.  comma-separated.  optional;
					will not add any tags if empty or missing.
				</dd>
				<dt>deltags</dt>
				<dd>
					list of tag names to remove from the entry.  comma-separated.
					optional; will not remove any tags if empty or missing.
				</dd>
			</dl>

<?php
	}

	/**
	 * check availability of a url for a blog entry.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function checkurlAction($ajax) {
		if(isset($_GET['url']) && trim($_GET['url'])) {
			$id = isset($_GET['id']) ? +$_GET['id'] : 0;
			self::CheckUrl(trim($_GET['url']), $id, $ajax);
		} else
			$ajax->Fail('url is required.');
	}

	/**
	 * delete a draft blog entry.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function deleteAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
				if($db->real_query('delete from blog_entries where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
					if($db->affected_rows) {}
					else
						$ajax->Fail('unable to delete entry.  it may be published or already deleted.');
				else
					$ajax->Fail('error deleting entry from database', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to delete a blog entry.');
		else
			$ajax->Fail('only the administrator can delete blog entries.  you might need to log in again.');
	}

	/**
	 * get blog entry information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($entry = $db->query('select e.url, e.title, coalesce(nullif(e.markdown, \'\'),e.content) as markdown, group_concat(t.name) as tags from blog_entries as e left join blog_entrytags as et on et.entry=e.id left join blog_tags as t on t.id=et.tag where e.id=\'' . +$_GET['id'] . '\' limit 1'))
					if($entry = $entry->fetch_object()) {
						$ajax->Data->url = $entry->url;
						$ajax->Data->title = $entry->title;
						$ajax->Data->content = $entry->markdown;
						$ajax->Data->tags = $entry->tags;
					} else
						$ajax->Fail('blog entry not found.');
				else
					$ajax->Fail('database error looking up entry for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit a blog entry.');
		else
			$ajax->Fail('only the administrator can edit blog entries.  you might need to log in again.');
	}

	/**
	 * get latest blog entries.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$tagid = isset($_GET['tagid']) && +$_GET['tagid'] ? +$_GET['tagid'] : false;
		$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;

		$select = 'select e.url, e.posted, e.title, left(e.content, locate(\'</p>\', e.content) + 3) as content, count(distinct c.id) as comments, group_concat(distinct t.name) as tags from '
			. ($tagid ? 'blog_entrytags as ft left join blog_entries as e on e.id=ft.entry ' : 'blog_entries as e ')
			. 'left join blog_comments as c on c.entry=e.id left join blog_entrytags as et on et.entry=e.id left join blog_tags as t on t.id=et.tag where '
			. ($tagid ? 'ft.tag=\'' . $tagid . '\' and ' : '')
			. 'e.status=\'published\' '
			. ($before ? 'and e.posted<\'' . $before . '\' ' : '')
			. 'group by e.id order by e.posted desc limit ' . self::MAXENTRIES;

		$ajax->Data->entries = [];
		if($entries = $db->query($select)) {
			$ajax->Data->lastdate = 0;
			while($entry = $entries->fetch_object()) {
				$ajax->Data->lastdate = +$entry->posted;
				$entry->posted = t7format::TimeTag('M j, Y', $entry->posted, t7format::DATE_LONG);
				$entry->tags = explode(',', $entry->tags);
				$entry->comments += 0;  // convert to integer
				$ajax->Data->entries[] = $entry;
			}
			$ajax->Data->hasMore = false;
			if(count($ajax->Data->entries)) {
				$more = 'select 1 from '
					. ($tagid ? 'blog_entrytags as ft left join blog_entries as e on e.id=ft.entry ' : 'blog_entries as e ')
					. 'where ' . ($tagid ? 'ft.tag=\'' . $tagid . '\' and ' : '')
					. 'e.status=\'published\' and e.posted<\'' . $ajax->Data->lastdate . '\' limit 1';
				if($more = $db->query($more))
					$ajax->Data->hasMore = $more->num_rows > 0;
				else
					$ajax->Fail('error checking if there are more blog entries', $db->errno . ' ' . $db->error);
			}
		} else
			$ajax->Fail('error getting latest blog entries', $db->errno . ' ' . $db->error);
	}

	/**
	 * publish a blog entry.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function publishAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
				if($db->real_query('update blog_entries set status=\'published\', posted=\'' . +time() . '\' where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
					if($db->affected_rows) {
						$db->real_query('update blog_tags as t inner join (select et.tag as tag, count(1) as count, max(e.posted) as lastused from blog_entrytags as et inner join blog_entrytags as ft on ft.tag=et.tag and ft.entry=\'' . $_POST['id'] . '\' left join blog_entries as e on e.id=et.entry where e.status=\'published\' group by et.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
						if($entry = $db->query('select url, title from blog_entries where id=\'' . +$_POST['id'] . '\' limit 1'))
							if($entry = $entry->fetch_object())
								t7send::Tweet('new blog: ' . $entry->title, t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $entry->url));
					} else
						$ajax->Fail('entry not updated.  this should only happen if the id doesn’t exist or the entry is already published.');
				else
					$ajax->Fail('database error publishing entry', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to publish a blog entry.');
		else
			$ajax->Fail('only the administrator can publish blog entries.  you might need to log in again.');
	}

	/**
	 * save a blog entry.  can update an existing entry or add a new entry
	 * depending on whether an id is provided.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['title']) && trim($_POST['title']) && isset($_POST['url']) && isset($_POST['content']) && trim($_POST['content'])) {
				$title = trim($_POST['title']);
				$url = trim($_POST['url']);
				if(!$url)
					$url = t7format::NameToUrl($title);
				$id = isset($_POST['id']) ? +$_POST['id'] : false;
				if(self::CheckUrl($url, $id, $ajax)) {
					$save = $id
						? 'update blog_entries set title=\'' . $db->escape_string($title) . '\', url=\'' . $db->escape_string($url) . '\', markdown=\'' . $db->escape_string($_POST['content']) . '\', content=\'' . $db->escape_string(t7format::Markdown($_POST['content'])) . '\' where id=\'' . +$id . '\' limit 1'
						: 'insert into blog_entries (title, url, markdown, content, posted) values (\'' . $db->escape_string($title) . '\', \'' . $db->escape_string($url) . '\', \'' . $db->escape_string($_POST['content']) . '\', \'' . $db->escape_string(t7format::Markdown($_POST['content'])) . '\', \'' . +time() . '\')';
					if($db->real_query($save)) {
						if(!$id)
							$id = $db->insert_id;
							$del = isset($_POST['deltags']) && $_POST['deltags'] ? explode(',', trim($_POST['deltags'])) : [];
							if(count($del))
								$db->real_query('delete from blog_entrytags where entry=\'' . +$id . '\' and tag in (select id from blog_tags where name in (trim(\'' . implode('\'), trim(\'', $del) . '\')))');
							$add = isset($_POST['addtags']) && $_POST['addtags'] ? explode(',', trim($_POST['addtags'])) : [];
							if(count($add)) {
								$db->query('insert into blog_tags (name) values (trim(\'' . implode('\')), (trim(\'', $add) . '\')) on duplicate key update name=name');
								$db->query('insert into blog_entrytags (entry, tag) select \'' . +$id . '\' as entry, id as tag from blog_tags where name in (trim(\'' . implode('\'), trim(\'', $add) . '\'))');
							}
							if($entry = $db->query('select url, status from blog_entries where id=\'' . +$id . '\' limit 1'))
								if($entry = $entry->fetch_object()) {
									if($entry->status == 'published' && (count($del) || count($add))) {
										$tags = array_keys(array_flip($del) + array_flip($add));
										$db->real_query('update blog_tags as t inner join (select et.tag as tag, count(1) as count, max(e.posted) as lastused from blog_entrytags as et left join blog_entries as e on e.id=et.entry left join blog_tags as tn on tn.id=et.tag where tn.name in (trim(\'' . implode('\'), trim(\'', $tags) . '\')) and e.status=\'published\' group by et.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
									}
									$ajax->Data->url = $entry->url;
								} else
									$ajax->Fail('saved entry but then couldn’t find it.');
							else
								$ajax->Fail('saved entry but got a database error looking it up', $db->errno . ' ' . $db->error);
					} else
						$ajax->Fail('database error saving blog entry', $db->errno . ' ' . $db->error);
				}
			} else
				$ajax->Fail('title, url, and content are required.');
		else
			$ajax->Fail('only the administrator can save blog entries.  you might need to log in again.');
	}

	/**
	 * make sure the url is valid and isn't already used, unless it's already used
	 * by this entry.
	 * @param string $url url segment to check
	 * @param int $id id of blog entry we're checking for, because it's okay if this entry is already using the url.
	 * @param t7ajax $ajax ajax object or reporting an error.  optional.
	 * @return boolean whether the url is valid and available.
	 */
	private static function CheckUrl($url, $id, $ajax) {
		global $db;
		if(t7format::ValidUrlPiece($url))
			if($chk = $db->query('select title from blog_entries where url=\'' . $db->escape_string($url) . '\' and not id=\'' . +$id . '\' limit 1'))
				if($chk = $chk->fetch_object())
					$ajax->Fail('url already in use by “' . $chk->title . '.”');
				else
					return true;
			else
				$ajax->Fail('error checking if blog entry url is available', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('url must be at least three characters and can only contain letters, digits, periods, dashes, and underscores.');
		return false;
	}
}
blogApi::Respond();
