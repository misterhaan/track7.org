<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for guides api requests.
 * @author misterhaan
 */
class guidesApi extends t7api {
	const MAXGUIDES = 9;

	/**
	 * write out the documentation for the guides api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=postdelete>post delete</h2>
			<p>delete a draft guide.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>guide id to delete.</dd>
			</dl>

			<h2 id=getedit>get edit</h2>
			<p>get guide information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>guide id to load for editing.</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest guides with most recent first.</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve guides with that tag.</dd>
				<dt>before</dt>
				<dd>specify a timestamp to only return guides before then.</dd>
			</dl>

			<h2 id=postpublish>post publish</h2>
			<p>publish a guide.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>guide id to publish.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>save edits to a guide or add a new guide.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>guide id to save.  will add a new guide if empty or missing.</dd>
				<dt>guidejson</dt>
				<dd>guide information as json.  required.</dd>
			</dl>

<?php
	}

	/**
	 * delete a draft blog entry.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function deleteAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
				// pages and tag links are automatically deleted via foreign key
				if($db->real_query('delete from guides where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
					if($db->affected_rows) {
					} else
						$ajax->Fail('unable to delete guide.  it may be published or already deleted.');
				else
					$ajax->Fail('error deleting guide from database', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to delete a guide.');
		else
			$ajax->Fail('only the administrator can delete guides.  you might need to log in again.');
	}

	/**
	 * get guide information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($guide = $db->query('select g.status, g.title, g.url, if(length(g.summary_markdown) > 0, g.summary_markdown, g.summary) as summary, g.level, group_concat(t.name) as tags from guides as g left join guide_taglinks as tl on tl.guide=g.id left join guide_tags as t on t.id=tl.tag where g.id=\'' . +$_GET['id'] . '\' group by g.id'))
					if($guide = $guide->fetch_object()) {
						$ajax->Data->status = $guide->status;
						$ajax->Data->title = $guide->title;
						$ajax->Data->url = $guide->url;
						$ajax->Data->summary = $guide->summary;
						$ajax->Data->level = $guide->level;
						$ajax->Data->tags = $guide->tags;
						$ajax->Data->pages = [];
						if($pages = $db->query('select id, heading, if(length(markdown) > 0, markdown, html) as markdown from guide_pages where guide=\'' . +$_GET['id'] . '\' order by number'))
							while($page = $pages->fetch_object())
								$ajax->Data->pages[] = $page;
						else
							$ajax->Fail('database error looking up guide pages for editing', $db->errno . ' ' . $db->error);
					} else
						$ajax->Fail('guide not found.');
				else
					$ajax->Fail('database error looking up guide for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit a guide.');
		else
			$ajax->Fail('only the administrator can edit guides.  you might need to log in again.');
	}

	/**
	 * get latest guides.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$tagid = isset($_GET['tagid']) && +$_GET['tagid'] ? +$_GET['tagid'] : false;
		$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;

		$guides = 'select g.url, g.posted, g.updated, g.title, group_concat(distinct t.name order by t.name separator \',\') as tags, g.summary, g.level, g.rating, g.votes, g.views, count(distinct c.id) as comments from'
			. ($tagid ? ' guide_taglinks as gtl left join guides as g on g.id=gtl.guide' : ' guides as g')
			. ' left join guide_comments as c on c.guide=g.id left join guide_taglinks as tl on tl.guide=g.id left join guide_tags as t on t.id=tl.tag where'
			. ($tagid ? ' gtl.tag=\'' . $tagid . '\' and' : '')
			. ' g.status=\'published\''
			. ($before ? ' and g.posted<\'' . $before . '\'' : '')
			. ' group by g.id order by g.updated desc limit ' . self::MAXGUIDES;
		if($guides = $db->query($guides)) {
			$ajax->Data->guides = [];
			$ajax->Data->oldest = 0;
			while($guide = $guides->fetch_object()) {
				$ajax->Data->oldest = +$guide->updated;
				$guide->rating += 0;
				$guide->votes += 0;
				$guide->comments += 0;
				if($guide->views > 9999)
					$guide->views = number_format($guide->views);
				$guide->posted = $guide->posted == $guide->updated
					? t7format::LocalDate(t7format::DATE_LONG, $guide->posted)
					: '';
				$guide->updated = t7format::TimeTag('smart', $guide->updated, t7format::DATE_LONG);
				$guide->tags = explode(',', $guide->tags);
				$ajax->Data->guides[] = $guide;
			}
			$ajax->Data->hasMore = false;
			$more = $tagid
			? 'select 1 from guide_taglinks as tl left join guides as g on g.id=tl.guide where tl.tag=\'' . $tagid . '\' and g.status=\'published\' and g.updated<\'' . +$ajax->Data->oldest . '\''
			: 'select 1 from guides where status=\'published\' and updated<\'' . +$ajax->Data->oldest . '\'';
			if($more = $db->query($more))
				$ajax->Data->hasMore = $more->num_rows > 0;
		} else
			$ajax->Fail('error looking up guides', $db->errno . ' ' . $db->error);
	}

	/**
	 * publish a guide.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function publishAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
				if($db->real_query('update guides set status=\'published\', posted=\'' . +time() . '\', updated=\'' . +time() . '\' where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
					if($db->affected_rows) {
						$db->real_query('update guide_tags as t inner join (select gt.tag as tag, count(1) as count, max(g.updated) as lastused from guide_taglinks as gt inner join guide_taglinks as ft on ft.tag=gt.tag and ft.guide=\'' . +$_POST['id'] . '\' left join guides as g on g.id=gt.guide where g.status=\'published\' group by gt.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
						if($guide = $db->query('select url, title from guides where id=\'' . +$_POST['id'] . '\' limit 1'))
							if($guide = $guide->fetch_object())
								t7send::Tweet('new guide: ' . $guide->title, t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $guide->url));
					} else
						$ajax->Fail('guide not updated.  this should only happen if the id doesn’t exist or the guide is already published.');
				else
					$ajax->Fail('database error publishing guide', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to publish a guide.');
		else
			$ajax->Fail('only the administrator can publish guides.  you might need to log in again.');
	}
	
	/**
	 * save a guide.  can update an existing guide or add a new guide depending on
	 * whether an id is provided.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['guidejson']) && $guide = json_decode($_POST['guidejson']))
				if(isset($guide->status, $guide->title, $guide->url, $guide->summary, $guide->level, $guide->pages) && trim($guide->title) && in_array($guide->status, ['draft', 'published']) && in_array($guide->level, ['beginner', 'intermediate', 'advanced']) && is_array($guide->pages) && count($guide->pages)) {
					$guide->title = trim($guide->title);
					$guide->url = trim($guide->url);
					if(!$guide->url)
						$guide->url = t7format::NameToUrl($guide->title);
					$id = isset($_POST['id']) ? +$_POST['id'] : false;
					if(self::CheckUrl('guides', 'title', $guide->url, $id, $ajax)) {
						$db->autocommit(false);
						$q = 'guides set url=\'' . $db->escape_string($guide->url)
							. '\', title=\'' . $db->escape_string(trim($guide->title))
							. '\', summary_markdown=\'' . $db->escape_string(trim($guide->summary))
							. '\', summary=\'' . $db->escape_string(t7format::Markdown(trim($guide->summary)))
							. '\', level=\'' . $db->escape_string($guide->level) . '\'';
						if($guide->status != 'published' || !$guide->correctionsOnly)
							$q .= ', updated=\'' . +time() . '\'';
						$q = $id
							? 'update ' . $q . ' where id=\'' . +$id . '\' limit 1'
							: 'insert into ' . $q . ', author=1';
						if($db->real_query($q)) {
							if(!$id)
								$id = $db->insert_id;
							if(count($guide->deletedPageIDs))
								if(!$db->real_query('delete from guide_pages where id in (' . implode(',', $guide->deletedPageIDs) . ')'))
									$ajax->Fail('database error removing deleted pages', $db->errno . ' ' . $db->error);
							foreach($guide->pages as $index => $page) {
								$q = 'guide_pages set number=\'' . ($index + 1) . '\', heading=\'' . $db->escape_string(trim($page->heading)) . '\', markdown=\'' . $db->escape_string(trim($page->markdown)) . '\', html=\'' . $db->escape_string(t7format::Markdown(trim($page->markdown))) . '\'';
								$q = $page->id
									? 'update ' . $q . ' where id=\'' . +$page->id . '\' limit 1'
									: 'insert into ' . $q . ', guide=\'' . +$id . '\'';
								if(!$db->real_query($q))
									$ajax->Fail('database error saving page ' . ($index + 1), $db->errno . ' ' . $db->error);
							}
							if(!$ajax->Data->fail) {
								$db->commit();
								$db->autocommit(true);
								$ajax->Data->url = $guide->url;
								$del = $guide->deltags ? explode(',', $db->escape_string($guide->deltags)) : [];
								if(count($del))
									$db->real_query('delete from guide_taglinks where guide=\'' . +$id . '\' and tag in (select id from guide_tags where name in (\'' . implode('\', \'', $del) . '\'))');
								$add = $guide->addtags ? explode(',', $db->escape_string($guide->addtags)) : [];
								if(count($add)) {
									$db->query('insert into guide_tags (name) values (trim(\'' . implode('\')), (trim(\'', $add) . '\')) on duplicate key update name=name');
									$db->query('insert into guide_taglinks (guide, tag) select \'' . +$id . '\' as guide, id as tag from guide_tags where name in (trim(\'' . implode('\'), trim(\'', $add) . '\'))');
								}
								if($guide->status == 'published' && (count($del) || count($add))) {
									$tags = array_keys(array_flip($del) + array_flip($add));
									$db->real_query('update guide_tags as t inner join (select gt.tag as tag, count(1) as count, max(g.updated) as lastused from guide_taglinks as gt left join guides as g on g.id=gt.guide left join guide_tags as tn on tn.id=gt.tag where tn.name in (trim(\'' . implode('\'), trim(\'', $tags) . '\')) and g.status=\'published\' group by gt.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
								}
							}  // error was logged from a failed page insert / update / delete query
						} else
							$ajax->Fail('database error saving guide data', $db->errno . ' ' . $db->error);
					}  // error was logged by CheckUrl()
				} else
					$ajax->Fail('guidejson must contain status, title, summary, level, and pages.  they also must be valid.');
			else
				$ajax->Fail('guidejson parameter is required and must be valid json', json_last_error() . ' ' . json_last_error_msg());
		else
			$ajax->Fail('only the administrator can save guides.  you might need to log in again.');
	}
}
guidesApi::Respond();
