<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for photos api requests.
 * @author misterhaan
 */
class photosApi extends t7api {
	const MAXPHOTOS = 24;
	
	/**
	 * write out the documentation for the photos api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest photos with most recent first.</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve photos with that tag.</dd>
				<dt>before</dt>
				<dd>specify a timestamp to only return photos before then.</dd>
			</dl>

<?php
	}

	/**
	 * get latest photos.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$tagid = isset($_GET['tagid']) ? +$_GET['tagid'] : 0;
		$before = isset($_GET['before']) ? +$_GET['before'] : 0;
		$photos = 'select p.url, p.posted, p.caption, count(c.photo) as comments from photos as p left join photos_comments as c on c.photo=p.id';
		if($tagid) {
			$photos .= ' where exists (select 1 from photos_taglinks where tag=\'' . $tagid . '\' and photo=p.id)';
			if($before)
				$photos .= ' and p.posted<\'' . $before . '\'';
		} elseif($before)
			$photos .= ' where p.posted<\'' . $before . '\'';
		$photos .= ' group by p.id order by p.posted desc limit ' . self::MAXPHOTOS;
		if($photos = $db->query($photos)) {
			$ajax->Data->photos = [];
			$ajax->Data->oldest = 0;
			while($photo = $photos->fetch_object()) {
				$ajax->Data->oldest = +$photo->posted;
				unset($photo->posted);
				$photo->comments += 0;
				$ajax->Data->photos[] = $photo;
			}
			$more = 'select 1 from photos as p where p.posted<\'' . $ajax->Data->oldest . '\'';
			if($tagid)
				$more .= ' and exists (select 1 from photos_taglinks where tag=\'' . $tagid . '\' and photo=p.id)';
			if($more = $db->query($more . ' limit 1'))
				$ajax->Data->hasMore = $more->num_rows > 0;
			else
				$ajax->Fail('error checking for more photos' . $db->errno . ' ' . $db->error);
		} else
			$ajax->Fail('error looking up photos', $db->errno . ' ' . $db->error);
	}
}
photosApi::Respond();
