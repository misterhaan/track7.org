<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for activity api requests.
 * @author misterhaan
 */
class activityApi extends t7api {
	const MAXITEMS = 9;

	/**
	 * write out the documentation for the activity api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
		?>
			<h2 id=getlatest>get latest</h2>
			<p>
				retrieves the lastest activity with most recent first.
			</p>
			<dl class=parameters>
				<dt>before</dt>
				<dd>
					specify a timestamp to only return activity before then.
				</dd>
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
		if($acts = t7contrib::GetAll($before, self::MAXITEMS)) {
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
}
activityApi::Respond();
