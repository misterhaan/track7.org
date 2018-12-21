<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for updates api requests.
 * @author misterhaan
 */
class updatesApi extends t7api {
	const MAX_UPDATE_GET = 16;

	/**
	 * write out the documentation for the updates api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=postadd>post add</h2>
			<p>adds a new update.</p>
			<dl class=parameters>
				<dt>markdown</dt>
				<dd>content of the update in markdown format.  required.</dd>
				<dt>posted</dt>
				<dd>
					when the update was made.  optional, uses current date and time if
					left blank.
				</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves a list of updates with the most recent first.</p>
			<dl class=parameters>
				<dt>oldest</dt>
				<dd>if specified, start with updates older than this timestamp.</dd>
				<dt>oldid</dt>
				<dd>
					start with updates with the same timestamp as oldest but with smaller
					ids than this.  required if oldest is provided, ignored if not.
				</dd>
			</dl>
<?php
	}

	/**
	 * add a new update.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function addAction($ajax) {
		global $db, $user;
		if($user->IsAdmin()) {
			$html = trim($_POST['markdown']);
			if($html) {
				$html = t7format::Markdown($html);
				$posted = trim($_POST['posted']);
				$posted = t7format::LocalStrtotime($posted);
				if(!$posted)
					$posted = time();
				if($save = $db->prepare('insert into update_messages (posted, html) values (?, ?)')) {
					if($save->bind_param('is', $posted, $html))
						if($save->execute()) {
							$ajax->Data->id = $save->insert_id;
							t7send::Tweet('track7 update', t7format::FullUrl('/updates/' . $ajax->Data->id));
						} else
							$ajax->Fail('error saving update:  ' . $save->error);
					else
						$ajax->Fail('error binding parameters to save update:  ' . $save->error);
					$save->close();
				} else
					$ajax->Fail('error preparing to save update:  ' . $db->error);
			} else
				$ajax->Fail('update is required.');
		} else
			$ajax->Fail('only the administrator can post a site update.');
	}

	/**
	 * get recent updates.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$oldest = isset($_GET['oldest']) ? +$_GET['oldest'] : 0;
		if(!$oldest)
			$oldest = null;
		$oldid = isset($_GET['oldid']) ? +$_GET['oldid'] : 0;
		if($us = $db->prepare('select u.id, u.posted, u.html, count(c.id) as comments from update_messages as u left join update_comments as c on c.message=u.id where ? is null or u.posted<? or u.posted=? and u.id<? group by u.id order by u.posted desc, u.id desc limit ' . self::MAX_UPDATE_GET)) {
			if($us->bind_param('iiii', $oldest, $oldest, $oldest, $oldid))
				if($us->execute())
					if($us->bind_result($id, $posted, $html, $comments)) {
						$ajax->Data->updates = [];
						$ajax->Data->oldest = 0;
						$ajax->Data->oldid = 0;
						while($us->fetch()) {
							$postdate = t7format::TimeTag('smart', $posted, t7format::DATE_LONG);
							$ajax->Data->updates[] = ['id' => $id, 'posted' => $postdate, 'html' => $html, 'comments' => $comments];
						}
						$us->close();
						$ajax->Data->oldest = $posted;
						$ajax->Data->oldid = $id;
						if($more = $db->query('select 1 from update_messages where posted<' . +$posted . ' or posted=' . +$posted . ' and id<' . +$id . ' limit 1'))
							$ajax->Data->hasmore = $more->num_rows > 0;
					} else
						$ajax->Fail('error binding results for updates', $us->errno . ' ' . $us->error);
				else
					$ajax->Fail('error getting updates', $us->errno . ' ' . $us->error);
			else
				$ajax->Fail('error binding paramaters to get updates', $us->errno . ' ' . $us->error);
	} else
			$ajax->Fail('error preparing to get updates', $db->errno . ' ' . $db->error);
	}
}
updatesApi::Respond();
