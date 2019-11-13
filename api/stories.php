<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for stories api requests.
 * @author misterhaan
 */
class storiesApi extends t7api {
	/**
	 * write out the documentation for the stories api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getlist>get list</h2>
			<p>
				retrieves the lastest stories with most recent first.  stories that are
				part of a series are grouped unde the series and sorted by the latest
				story added to the series.
			</p>

			<h2 id=getseries>get series</h2>
			<p>retrieves the stories in a series with most recent first.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>series id to load story list.</dd>
			</dl>

<?php
	}

	/**
	 * get latest stotries.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		if($stories = $db->query('select max(ifnull(ss.lastposted,s.posted)) as posted, max(if(ss.url is null,s.url,concat(ss.url,\'/\'))) as url, max(ifnull(ss.title,s.title)) as title, max(ifnull(ss.deschtml,s.deschtml)) as deschtml, max(ss.numstories) as numstories from stories as s left join stories_series as ss on ss.id=s.series where s.published=1 group by ifnull(s.series,-s.id) order by posted desc')) {
			$ajax->Data->stories = [];
			while($story = $stories->fetch_object()) {
				if($story->posted > 100)
					$story->posted = t7format::TimeTag('M j, Y', $story->posted, t7format::DATE_LONG);
				else
					$story->posted = false;
				$ajax->Data->stories[] = $story;
			}
		} else
			$ajax->Fail('databasa error looking up stories', $db->errno . ' ' . $db->error);
	}

	/**
	 * get stories in a series.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function seriesAction($ajax) {
		global $db;
		if(isset($_GET['id']) && +$_GET['id'])
			if($stories = $db->query('select posted, url, title, deschtml from stories where published=1 and series=\'' . +$_GET['id'] . '\' order by posted')) {
				$ajax->Data->stories = [];
				while($story = $stories->fetch_object()) {
					if($story->posted > 100)
						$story->posted = t7format::TimeTag('M j, Y', $story->posted, t7format::DATE_LONG);
					else
						$story->posted = false;
					$ajax->Data->stories[] = $story;
				}
			} else
				$ajax->Fail('database error looking up stories in series', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('id parameter is required.');
	}
}
storiesApi::Respond();
