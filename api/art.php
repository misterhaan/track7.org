<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for art api requests.
 * @author misterhaan
 */
class artApi extends t7api {
	const MAXART = 24;
	const MAXARTSIZE = 800;
	const THUMBSIZE = 150;

	/**
	 * write out the documentation for the art api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>get art information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>art id to load for editing.</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest art with most recent first.</p>
			<dl class=parameters>
				<dt>tagid</dt>
				<dd>specify a tag id to only retrieve art with that tag.</dd>
				<dt>beforetime</dt>
				<dd>specify a timestamp to only return art before then.</dd>
				<dt>beforeid</dt>
				<dd>
					specify an id to include art from the beforetime but earlier id than
					this.  required if beforetime is specified, ignored if beforetime is
					zero, blank, or missing.
				</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to art or add new art.  only available to admin. accepts
				image file upload named "art" which is required for new art and optional
				for existing (will overwrite old art image if specified).
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>art id to save.  will add new art if empty or missing.</dd>
				<dt>title</dt>
				<dd>art title as plain text.  required.</dd>
				<dt>url</dt>
				<dd>url portion specific to this art.  required.</dd>
				<dt>descmd</dt>
				<dd>description of the art in markdown format.  required.</dd>
				<dt>deviation</dt>
				<dd>
					url portion specific to this art on deviantart.  optional; will not
					link to deviantart unless present.
				</dd>
				<dt>addtags</dt>
				<dd>
					list of tag names to add to the art.  comma-separated.  optional;
					will not add any tags if empty or missing.
				</dd>
				<dt>deltags</dt>
				<dd>
					list of tag names to remove from the art.  comma-separated.
					optional; will not remove any tags if empty or missing.
				</dd>
				<dt>originalurl</dt>
				<dd>
					when editing existing art, this value is compared against url.  if
					they’re different, the files get renamed.
				</dd>
			</dl>

<?php
	}

	/**
	 * get art information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($art = $db->query('select a.title, a.url, i.ext, coalesce(nullif(descmd,\'\'),deschtml) as descmd, a.deviation, group_concat(t.name) as tags from art as a left join image_formats as i on i.id=a.format left join art_taglinks as at on at.art=a.id left join art_tags as t on t.id=at.tag where a.id=\'' . +$_GET['id'] . '\' group by a.id'))
					if($art = $art->fetch_object())
						$ajax->MergeData($art);
					else
						$ajax->Fail('cannot find art.');
				else
					$ajax->Fail('database error looking up art for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit art.');
		else
			$ajax->Fail('only the administrator can edit art.  you might need to log in again.');
	}

	/**
	 * get latest art.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$tagid = isset($_GET['tagid']) ? +$_GET['tagid'] : 0;
		$beforetime = isset($_GET['beforetime']) ? +$_GET['beforetime'] : false;
		$beforeid = $beforetime !== false && isset($_GET['beforeid']) ? +$_GET['beforeid'] : 0;
		$arts = 'select a.id, a.url, i.ext, a.posted from art as a left join image_formats as i on i.id=a.format';
		if($tagid) {
			$arts .= ' where exists (select 1 from art_taglinks where tag=\'' . $tagid . '\' and art=a.id)';
			if($beforetime !== false)
				$arts .= '  and (a.posted<\'' . $beforetime . '\' or a.posted=\'' . $beforetime . '\' and a.id<\'' . $beforeid . '\')';
		} elseif($beforetime !== false)
			$arts .= ' where a.posted<\'' . $beforetime . '\' or a.posted=\'' . $beforetime . '\' and a.id<\'' . $beforeid . '\'';
		$arts .= ' order by a.posted desc, a.id desc limit ' . self::MAXART;
		if($arts = $db->query($arts)) {
			$ajax->Data->arts = [];
			$ajax->Data->oldest = '';
			$ajax->Data->lastid = '';
			while($art = $arts->fetch_object()) {
				$ajax->Data->oldest = +$art->posted;
				$ajax->Data->lastid = +$art->id;
				unset($art->posted, $art->id);
				$ajax->Data->arts[] = $art;
			}
			$more = 'select 1 from art as a where (a.posted<\'' . $ajax->Data->oldest . '\' or a.posted=\'' . $ajax->Data->oldest .'\' and a.id<\'' . $ajax->Data->lastid . '\')';
			if($tagid)
				$more .= ' and exists (select 1 from art_taglinks where tag=\'' . $tagid . '\' and art=a.id)';
			if($more = $db->query($more . ' limit 1'))
				$ajax->Data->hasMore = $more->num_rows > 0;
			else
				$ajax->Fail('error checking for more art' . $db->errno . ' ' . $db->error);
		} else
			$ajax->Fail('database error looking up art', $db->errno . ' ' . $db->error);
	}

	/**
	 * save changes to art or add new art.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['title']) && trim($_POST['title']) && isset($_POST['descmd']) && trim($_POST['descmd'])) {
				$id = isset($_POST['id']) ? +$_POST['id'] : false;
				if($id || isset($_FILES['art']) && $_FILES['art']['size']) {
					$title = trim($_POST['title']);
					$url = isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : t7format::NameToUrl($title);
					if(self::CheckUrl('art', 'title', $url, $id, $ajax)) {
						$deviation = isset($_POST['deviation']) && trim($_POST['deviation']) ? trim($_POST['deviation']) : '';
						$ext = false;
						if(isset($_FILES['art']) && $_FILES['art']['size']) {
							$ext = t7file::GetImageExtension($_FILES['art']);
							self::SaveUploadedArt($_FILES['art'], $url, $ext);
						}
						$ins = 'art set title=\'' . $db->escape_string($title) . '\', url=\'' . $db->escape_string($url) . '\', ' . ($ext ? 'format=(select id from image_formats where ext=\'' . $ext . '\'), ' : '') . 'descmd=\'' . $db->escape_string(trim($_POST['descmd'])) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown(trim($_POST['descmd']))) . '\', deviation=\'' . $db->escape_string($deviation) . '\'';
						$ins = $id
							? 'update ' . $ins . ' where id=\'' . +$id . '\' limit 1'
							: 'insert into ' . $ins . ', posted=\'' . +time() . '\'';
						if($db->real_query($ins)) {
							if(!$id)
								t7send::Tweet('new art: ' . $title, t7format::FullUrl('/art/' . $url));
							elseif($url != $_POST['originalurl'] && $_POST['originalurl'] == t7format::NameToUrl($_POST['originalurl'])) {
								$path = $_SERVER['DOCUMENT_ROOT'] . '/art/img/';
								if($ext) {
									unlink($path . $_POST['originalurl'] . '.jpg');
									unlink($path . $_POST['originalurl'] . '.jpeg');
									unlink($path . $_POST['originalurl'] . '.png');
									unlink($path . $_POST['originalurl'] . '-prev.jpg');
									unlink($path . $_POST['originalurl'] . '-prev.jpeg');
									unlink($path . $_POST['originalurl'] . '-prev.png');
								} elseif(file_exists($path . $_POST['originalurl'] . '.jpg')) {
									rename($path . $_POST['originalurl'] . '.jpg', $path . $url . '.jpg');
									rename($path . $_POST['originalurl'] . '-prev.jpg', $path . $url . '-prev.jpg');
								} elseif(file_exists($path . $_POST['originalurl'] . '.jpeg')) {
									rename($path . $_POST['originalurl'] . '.jpeg', $path . $url . '.jpeg');
									rename($path . $_POST['originalurl'] . '-prev.jpeg', $path . $url . '-prev.jpeg');
								} elseif(file_exists($path . $_POST['originalurl'] . '.png')) {
									rename($path . $_POST['originalurl'] . '.png', $path . $url . '.png');
									rename($path . $_POST['originalurl'] . '-prev.png', $path . $url . '-prev.png');
								}
							}
							$del = isset($_POST['deltags']) && $_POST['deltags'] ? explode(',', $db->escape_string($_POST['deltags'])) : [];
							if(count($del))
								$db->real_query('delete from art_taglinks where art=\'' . +$id . '\' and tag in (select id from art_tags where name in (trim(\'' . implode('\'), trim(\'', $del) . '\')))');
							$add = isset($_POST['addtags']) && $_POST['addtags'] ? explode(',', $db->escape_string($_POST['addtags'])) : [];
							if(count($add)) {
								$db->real_query('insert into art_tags (name) values (trim(\'' . implode('\')), (trim(\'', $add) . '\')) on duplicate key update name=name');
								$db->real_query('insert into art_taglinks (art, tag) select \'' . +$id . '\' as art, id as tag from art_tags where name in (trim(\'' . implode('\'), trim(\'', $add) . '\'))');
							}
							if(count($del) || count($add)) {
								$tags = array_keys(array_flip($del) + array_flip($add));
								$db->real_query('update art_tags as t inner join (select tl.tag as tag, count(1) as count, max(a.posted) as lastused from art_taglinks as tl left join art as a on a.id=tl.art left join art_tags as tn on tn.id=tl.tag where tn.name in (trim(\'' . implode('\'), trim(\'', $tags) . '\')) group by tl.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
							}
							$ajax->Data->url = $url;
						} else
							$ajax->Fail('database error saving art', $db->errno . ' ' . $db->error);
					}  // CheckUrl() adds its own failure message
				} else
					$ajax->Fail('image file must be included with new art.');
			} else
				$ajax->Fail('title and descmd are required.');
		else
			$ajax->Fail('only the administrator can edit art.  you might need to log in again.');
	}

	/**
	 * saves an uploaded art image and its thumbnail.
	 * @param array $image the part of $_FILES that contains information on the uploaded art image.
	 * @param string $name filename without extension the art should be saved as.
	 * @param string $ext extension to use when saving the art to files.
	 */
	private static function SaveUploadedArt($image, $name, $ext) {
		$name = $_SERVER['DOCUMENT_ROOT'] . '/art/img/' . $name;
		$dests = [
			$name . '.' . $ext => self::MAXARTSIZE,
			$name . '-prev.' . $ext => self::THUMBSIZE
		];
		t7file::SaveUploadedImage($image, $ext, $dests);
	}
}
artApi::Respond();
