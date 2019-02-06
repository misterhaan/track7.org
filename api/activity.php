<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for activity api requests.
 * @author misterhaan
 */
class activityApi extends t7api {
	/**
	 * write out the documentation for the activity api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
		?>
			<h2 id=getlatest>get latest</h2>
			<p>retrieves the lastest activity with most recent first.</p>
			<dl class=parameters>
				<dt>before</dt>
				<dd>specify a timestamp to only return activity before then.</dd>
			</dl>

			<h2 id=getuser>get user</h2>
			<p>retrieves the lastest activity for a user with most recent first.</p>
			<dl class=parameters>
				<dt>before</dt>
				<dd>specify a timestamp to only return activity before then.</dd>
				<dt>user</dt>
				<dd>specify a user id to only return activity from that user.</dd>
			</dl>
<?php
	}

	/**
	 * get latest activity.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function latestAction($ajax) {
		global $db;
		$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;
		if($acts = t7contrib::GetAll($before)) {
			$ajax->Data->acts = [];
			$ajax->Data->latest = false;
			while($act = $acts->fetch_object()) {
				$ajax->Data->latest = $act->posted;
				$act->posted = t7format::TimeTag('smart', $act->posted, t7format::DATE_LONG);
				$act->prefix = t7contrib::Prefix($act->conttype);
				$act->postfix = t7contrib::Postfix($act->conttype);
				$act->hasmore += 0;  // convert to numeric
				$ajax->Data->acts[] = $act;
			}
			$ajax->Data->more = t7contrib::More($ajax->Data->latest);
		} else
			$ajax->Fail('error looking up activity', $db->errno . ' ' . $db->error);
	}

	/**
	 * get user's latest activity.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function userAction($ajax) {
		global $db, $user;
		$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;
		$userid = isset($_GET['user']) && +$_GET['user'] ? +$_GET['user'] : $user->ID;
		if($acts = t7contrib::GetUser($userid, $before)) {
			$ajax->Data->acts = [];
			$ajax->Data->latest = false;
			while($act = $acts->fetch_object()) {
				$ajax->Data->latest = $act->posted;
				$act->posted = t7format::TimeTag('ago', $act->posted, t7format::DATE_LONG);
				$act->action = t7contrib::ActionWords($act->conttype);
				$ajax->Data->acts[] = $act;
			}
			$ajax->Data->more = t7contrib::More($ajax->Data->latest, $userid);
		} else
			$ajax->Fail('error looking up activity', $db->errno . ' ' . $db->error);
	}
}
activityApi::Respond();
