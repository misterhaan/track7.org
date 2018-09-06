<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for legos api requests.
 * @author misterhaan
 */
class legosApi extends t7api {
	const MAXLEGO = 24;
	const THUMBSIZE = 150;
	const FULLSIZE = 800;
	const DATAFILEPATH = '/lego/data/';

	/**
	 * write out the documentation for the legos api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>
				retrieves details of a lego model for editing.  only available to admin.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>lego model id to load for editing</dd>
			</dl>

			<h2 id=getlist>get list</h2>
			<p>retrieves the lastest lego models with most recent first.</p>
			<dl class=parameters>
				<dt>before</dt>
				<dd>specify a timestamp to only return lego models before then.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to a lego model or add a new lego model.  only available to
				admin.  accepts file uploads named "image," "ldraw," and "instructions"
				which are required for new lego models and optional for existing (will
				overwrite old files if specified).  the image file must be a png while
				the ldraw and instructions files must be zip files.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					lego model id to save.  will add a new lego model if empty or missing.
				</dd>
				<dt>title</dt>
				<dd>name of the lego model as plain text.  required.</dd>
				<dt>url</dt>
				<dd>
					url portion specific to this lego model.  optional;  generates from
					the title if blank or missing.
				</dd>
				<dt>pieces</dt>
				<dd>number of pieces in this lego model.  required.</dd>
				<dt>descmd</dt>
				<dd>description of the lego model in markdown format.  required.</dd>
				<dt>originalUrl</dt>
				<dd>
					when editing an existing lego model, this value is compared against
					url.  if they’re different, the files get renamed.
				</dd>
			</dl>

<?php
	}

	/**
	 * get lego model information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($lego = $db->query('select title, url, pieces, coalesce(nullif(descmd,\'\'),deschtml) as descmd from lego_models where id=\'' . +$_GET['id'] . '\''))
					if($lego = $lego->fetch_object()) {
						$ajax->Data->title = $lego->title;
						$ajax->Data->url = $lego->url;
						$ajax->Data->pieces = +$lego->pieces;
						$ajax->Data->descmd = $lego->descmd;
					} else
						$ajax->Fail('cannot find lego model.');
				else
					$ajax->Fail('database error looking up lego model for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit a lego model.');
		else
			$ajax->Fail('only the administrator can edit lego models.  you might need to log in again.');
	}

	/**
	 * get latest lego models.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		$legoq = 'select url, title, posted from lego_models';
		if(isset($_GET['before']) && +$_GET['before'])
			$legoq .= ' where posted<\'' . +$_GET['before'] . '\'';
		$legoq .= ' order by posted desc, id desc limit ' . self::MAXLEGO;
		$ajax->Data->legos = [];
		$ajax->Data->oldest = 0;
		if($legos = $db->query($legoq)) {
			while($lego = $legos->fetch_object()) {
				$ajax->Data->oldest = $lego->posted;
				unset($lego->posted);
				$ajax->Data->legos[] = $lego;
			}
			$more = 'select 1 from lego_models where posted<\'' . $ajax->Data->oldest . '\' limit 1';
			if($more = $db->query($more))
				$ajax->Data->hasMore = $more->num_rows > 0;
			else
				$ajax->Fail('database error checking if there are more lego models', $db->errno . ' ' . $db->error);
		} else
			$ajax->Fail('database error looking up lego models', $db->errno . ' ' . $db->error);
	}

	/**
	 * save changes to a lego model or add a new lego model.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['title']) && trim($_POST['title']) && isset($_POST['descmd']) && trim($_POST['descmd'])) {
				$id = isset($_POST['id']) ? +$_POST['id'] : 0;
				$title = trim($_POST['title']);
				$url = isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : t7format::NameToUrl($title);
				if(self::CheckUrl('lego_models', 'title', $url, $id, $ajax)) {
					$pieces = isset($_POST['pieces']) ? +$_POST['pieces'] : 0;
					if(!$pieces || $pieces > 3 && $pieces < 10000)
						if($id || isset($_FILES['image']) && $_FILES['image']['size'] && isset($_FILES['ldraw']) && $_FILES['ldraw']['size'] && isset($_FILES['instructions']) && $_FILES['instructions']['size']) {
							$path = $_SERVER['DOCUMENT_ROOT'] . self::DATAFILEPATH;
							$filenameprefix = $path . $url;
							if(isset($_FILES['image']) && $_FILES['image']['size'])
								t7file::SaveUploadedImage($_FILES['image'], 'png', [$filenameprefix . '-thumb.png' => self::THUMBSIZE, $filenameprefix . '.png' => self::FULLSIZE]);
							if(isset($_FILES['ldraw']) && $_FILES['ldraw']['size'])
								move_uploaded_file($_FILES['ldraw']['tmp_name'], $filenameprefix . '-ldr.zip');
							if(isset($_FILES['instructions']) && $_FILES['instructions']['size'])
								move_uploaded_file($_FILES['instructions']['tmp_name'], $filenameprefix . '-img.zip');
							$ins = 'lego_models set title=\'' . $db->escape_string($title) . '\', url=\'' . $db->escape_string(trim($url)) . '\', ' . 'pieces=\'' . $pieces . '\', descmd=\'' . $db->escape_string(trim($_POST['descmd'])) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown(trim($_POST['descmd']))) . '\'';
							$ins = $id ? 'update ' . $ins . ' where id=\'' . $id . '\' limit 1' : 'insert into ' . $ins . ', posted=\'' . +time() . '\'';
							if($db->real_query($ins)) {
								$ajax->Data->url = $url;
								if(!$id)
									t7send::Tweet('new lego: ' . $title, t7format::FullUrl('/lego/' . $url));
								elseif($url != $_POST['originalurl'] && $_POST['originalurl'] == t7format::NameToUrl($_POST['originalurl'])) {
									rename($path . $_POST['originalurl'] . '.png', $path . $url . '.png');
									rename($path . $_POST['originalurl'] . '-thumb.png', $path . $url . 'thumb.png');
									rename($path . $_POST['originalurl'] . '-ldr.zip', $path . $url . '-ldr.zip');
									rename($path . $_POST['originalurl'] . '-img.zip', $path . $url . '-img.zip');
								}
							} else
								$ajax->Fail('database error saving lego model', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail('image, ldraw, and instructions files must be included with new lego models.');
					else
						$ajax->Fail('number of pieces must be between 3 and 9999 if specified.');
				}  // CheckUrl() logs its own error
			} else
				$ajax->Fail('title and descmd are required.');
		else
			$ajax->Fail('only the administrator can edit lego models.  you might need to log in again.');
	}
}
legosApi::Respond();
