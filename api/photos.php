<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for photos api requests.
 * @author misterhaan
 */
class photosApi extends t7api {
	const MAXPHOTOS = 24;
	const MAXPHOTOSIZE = 800;
	const THUMBSIZE = 150;

	/**
	 * write out the documentation for the photos api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>get photo information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>photo id to load for editing.</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest photos with most recent first.</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve photos with that tag.</dd>
				<dt>before</dt>
				<dd>specify a timestamp to only return photos before then.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to a photo or add a new photo.  only available to admin.
				accepts photo file upload named "photo" which is required for new
				photos and optional for existing (will overwrite old photo if specified).
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>photo id to save.  will add a new photo if empty or missing.</dd>
				<dt>caption</dt>
				<dd>photo caption as plain text.  required.</dd>
				<dt>url</dt>
				<dd>url portion specific to this photo.  required.</dd>
				<dt>youtube</dt>
				<dd>
					url portion specific to this video on youtube.  optional; will not
					show video unless present.
				</dd>
				<dt>storymd</dt>
				<dd>story of the photo in markdown format.  required.</dd>
				<dt>taken</dt>
				<dd>
					date and time the photo was taken.  optional.  if empty or missing and
					a photo file is uploaded, this will be set based on the photo’s exif.
				</dd>
				<dt>year</dt>
				<dd>
					year the photo was taken.  optional.  if empty or missing this will be
					set based on the taken value, which might come from the photo’s exif.
				</dd>
				<dt>addtags</dt>
				<dd>
					list of tag names to add to the photo.  comma-separated.  optional;
					will not add any tags if empty or missing.
				</dd>
				<dt>deltags</dt>
				<dd>
					list of tag names to remove from the photo.  comma-separated.
					optional; will not remove any tags if empty or missing.
				</dd>
			</dl>

<?php
	}

	/**
	 * get photo information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($photo = $db->query('select p.caption, p.url, p.youtube, coalesce(nullif(p.storymd,\'\'),p.story) as storymd, p.taken, p.year, group_concat(t.name) as tags from photos as p left join photos_taglinks as pt on pt.photo=p.id left join photos_tags as t on t.id=pt.tag where p.id=\'' . +$_GET['id'] . '\' group by p.id'))
					if($photo = $photo->fetch_object()) {
						$ajax->Data->caption = $photo->caption;
						$ajax->Data->url = $photo->url;
						$ajax->Data->youtube = $photo->youtube;
						$ajax->Data->storymd = $photo->storymd;
						$ajax->Data->taken = $photo->taken ? $photo->taken = t7format::LocalDate('Y-m-d g:i:s a', $photo->taken) : "";
						$ajax->Data->year = $photo->year;
						$ajax->Data->tags = $photo->tags;
					} else
						$ajax->Fail('cannot find photo.');
				else
					$ajax->Fail('database error looking up photo for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit a photo.');
		else
			$ajax->Fail('only the administrator can edit photos.  you might need to log in again.');
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

	/**
	 * save changes to a photo or add a new photo.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['caption']) && trim($_POST['caption']) && isset($_POST['storymd']) && trim($_POST['storymd'])) {
				$id = isset($_POST['id']) ? +$_POST['id'] : false;
				if($id || isset($_FILES['photo']) && $_FILES['photo']['size']) {
					$caption = trim($_POST['caption']);
					$url = isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : t7format::NameToUrl($caption);
					if(self::CheckUrl('photos', 'caption', $url, $id, $ajax)) {
						$youtube = isset($_POST['youtube']) && trim($_POST['youtube']) ? trim($_POST['youtube']) : '';
						if(isset($_FILES['photo']) && $_FILES['photo']['size']) {
							$exif = exif_read_data($_FILES['photo']['tmp_name'], 'EXIF', true);
							self::SaveUploadedPhoto($_FILES['photo'], $url, $exif, $youtube);
							if((!isset($_POST['taken']) || !$_POST['taken']) && isset($exif['EXIF']) && isset($exif['EXIF']['DateTimeOriginal']))
								$_POST['taken'] = $exif['EXIF']['DateTimeOriginal'];
						}
						$taken = isset($_POST['taken']) && $_POST['taken'] ? t7format::LocalStrtotime($_POST['taken']) : '';
						$year = isset($_POST['year']) && $_POST['year'] ? $_POST['year'] : $taken ? t7format::LocalDate('Y', $taken) : '';
						$q = 'photos set caption=\'' . $db->escape_string($caption) . '\', url=\'' . $db->escape_string($url) . '\', youtube=\'' . $db->escape_string($youtube) . '\', storymd=\'' . $db->escape_string($_POST['storymd']) . '\', story=\'' . $db->escape_string(t7format::Markdown(trim($_POST['storymd']))) . '\', taken=' . ($taken ? '\'' . +$taken . '\'' : 'null') . ', year=' . +$year;
						$q = $id
							? 'update ' . $q . ' where id=\'' . +$id . '\' limit 1'
							: 'insert into ' . $q . ', posted=\'' . +time() . '\'';
						if($db->real_query($q)) {
							if(!$id) {
								$id = $db->insert_id;
								t7send::Tweet(($youtube ? 'new video: ' : 'new photo: ') . $caption, t7format::FullUrl('/album/' . $url));
							} elseif($url != $_POST['originalurl'] && $_POST['originalurl'] == t7format::NameToUrl($_POST['originalurl'])) {
								$path = $_SERVER['DOCUMENT_ROOT'] . '/album/photos/';
								rename($path . $_POST['originalurl'] . '.jpeg', $path . $url . '.jpeg');
								rename($path . $_POST['originalurl'] . '.jpg', $path . $url . '.jpg');
							}
							$del = isset($_POST['deltags']) && $_POST['deltags'] ? explode(',', trim($_POST['deltags'])) : [];
							if(count($del))
								$db->real_query('delete from photos_taglinks where photo=\'' . +$id . '\' and tag in (select id from photos_tags where name in (trim(\'' . implode('\'), trim(\'', $del) . '\')))');
							$add = isset($_POST['addtags']) && $_POST['addtags'] ? explode(',', trim($_POST['addtags'])) : [];
							if(count($add)) {
								$db->query('insert into photos_tags (name) values (trim(\'' . implode('\')), (trim(\'', $add) . '\')) on duplicate key update name=name');
								$db->query('insert into photos_taglinks (photo, tag) select \'' . +$id . '\' as photo, id as tag from photos_tags where name in (trim(\'' . implode('\'), trim(\'', $add) . '\'))');
							}
							if(count($del) || count($add)) {
								$tags = array_keys(array_flip($del) + array_flip($add));
								$db->real_query('update photos_tags as t inner join (select tl.tag as tag, count(1) as count, max(p.posted) as lastused from photos_taglinks as tl left join photos as p on p.id=tl.photo left join photos_tags as tn on tn.id=tl.tag where tn.name in (trim(\'' . implode('\'), trim(\'', $tags) . '\')) group by tl.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
							}
							$ajax->Data->url = $url;
						} else
							$ajax->Fail('error saving photo', $db->errno . ' ' . $db->error);
					}
				} else
					$ajax->Fail('image file must be included with new photos.');
			} else
				$ajax->Fail('caption and storymd are required.');
		else
			$ajax->Fail('only the administrator can edit photos.  you might need to log in again.');
	}

	/**
	 * saves an uploaded photo and its thumbnail.
	 * @param array $photo the part of $_FILES that contains information on the uploaded photo.
	 * @param string $name filename without extension the photo should be saved as.
	 * @param array $exif the photo's exif data, used for rotating.
	 * @param bool $thumbonly whether only a thumbnail should be saved.
	 */
	private static function SaveUploadedPhoto($photo, $name, $exif, $thumbonly) {
		$size = getimagesize($photo['tmp_name']);
		$image = imagecreatefromjpeg($photo['tmp_name']);
		$name = $_SERVER['DOCUMENT_ROOT'] . '/album/photos/' . $name;
		if(isset($exif['IFD0']['Orientation']))
			switch($exif['IFD0']['Orientation']) {
				case 3:
					$image = imagerotate($image, 180, 0);
					break;
				case 6:
					$image = imagerotate($image, -90, 0);
					$tmp = $size[0];
					$size[0] = $size[1];
					$size[1] = $tmp;
					break;
				case 8:
					$image = imagerotate($image, 90, 0);
					$tmp = $size[0];
					$size[0] = $size[1];
					$size[1] = $tmp;
					break;
		}
		$aspect = $size[0] / $size[1];
		if($thumbonly)
			unlink($photo['tmp_name']);
		else
			if($size[0] > self::MAXPHOTOSIZE || $size[1] > self::MAXPHOTOSIZE) {
				self::SaveResizedImage($image, $size[0], $size[1], $aspect, self::MAXPHOTOSIZE, $name . '.jpeg');
				unlink($photo['tmp_name']);
			} else
				move_uploaded_file($photo['tmp_name'], $name . '.jpeg');
		self::SaveResizedImage($image, $size[0], $size[1], $aspect, self::THUMBSIZE, $name . '.jpg');
		imagedestroy($image);
	}

	/**
	 * resizes and image and saves it.
	 * @param resource $image the original image to resize and save.
	 * @param int $width original image width.
	 * @param int $height original image height.
	 * @param float $aspect image aspect ratio.
	 * @param int $max maximum height / width of the resized image.
	 * @param string $filename full path and filename to save the image.
	 */
	private static function SaveResizedImage($image, $width, $height, $aspect, $max, $filename) {
		if($aspect > 1) {
			$w = $max;
			$h = round($max / $aspect);
		} else {
			$h = $max;
			$w = round($max * $aspect);
		}
		$resized = imagecreatetruecolor($w, $h);
		imagecopyresampled($resized, $image, 0, 0, 0, 0, $w, $h, $width, $height);
		imagejpeg($resized, $filename);
		imagedestroy($resized);
	}
}
photosApi::Respond();
