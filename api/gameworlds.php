<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for validate api requests.
 * @author misterhaan
 */
class gameworldsApi extends t7api {
	/**
	 * write out the documentation for the gameworlds api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>get game world information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>game world id to load for editing.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to a game world or add a new game world.  only available to
				admin.  accepts zip file upload named "zip" and image file upload named
				"screenshot" which are required for new game worlds and optional for
				existing (will overwrite old files if specified).
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					game world id to save.  will add new game world if empty or missing.
				</dd>
				<dt>name</dt>
				<dd>game world name as plain text.  required.</dd>
				<dt>url</dt>
				<dd>
					url portion specific to this game world.  will be set from name if
					empty or missing.
				</dd>
				<dt>engine</dt>
				<dd>id for the engine this game world runs on.  required.</dd>
				<dt>desc</dt>
				<dd>description of the game world in markdown format.  required.</dd>
				<dt>released</dt>
				<dd>
					date and time the game world was released.  will be set to the current
					date and time if empty or missing.
				</dd>
				<dt>originalurl</dt>
				<dd>
					when editing an existing application, this value is compared against
					url.  if they’re different, the files get renamed.
				</dd>
			</dl>

<?php
	}

	/**
	 * get game world information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == $id = +$_GET['id'])
				if($sel = $db->prepare('select name, url, engine, descmd, dmzx, released from code_game_worlds where id=?'))
					if($sel->bind_param('i', $id))
						if($sel->execute())
							if($sel->bind_result($name, $url, $engine, $desc, $dmzx, $released))
								if($sel->fetch()) {
									$ajax->Data->name = $name;
									$ajax->Data->url = $url;
									$ajax->Data->engine = $engine;
									$ajax->Data->desc = $desc;
									$ajax->Data->dmzx = $dmzx;
									$ajax->Data->released = t7format::LocalDate('Y-m-d g:i:s a', $released);
								} else
									$ajax->Fail('error fetching game world information:  ' . $sel->error);
							else
								$ajax->Fail('database error binding result of game world lookup', $sel->errno . ' ' . $sel->error);
						else
							$ajax->Fail('database error looking up game world for editing', $sel->errno . ' ' . $sel->error);
					else
						$ajax->Fail('database error binding id to look up game world for editing', $sel->errno . ' ' . $db->error);
				else
					$ajax->Fail('database error preparing to look up game world for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit game world.');
		else
			$ajax->Fail('only the administrator can edit game worlds.  you might need to log in again.');
	}

	/**
	 * save changes to a game world or add a new game world.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['name']) && ($name = trim($_POST['name'])) && isset($_POST['engine']) && ($engine = +$_POST['engine']) && isset($_POST['desc']) && ($desc = trim($_POST['desc']))) {
				$id = isset($_POST['id']) ? +$_POST['id'] : 0;
				if($id || isset($_FILES['zip']) && $_FILES['zip']['size'] && isset($_FILES['screenshot']) && $_FILES['screenshot']['size']) {
					$url = isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : t7format::NameToUrl($name);
					if(self::CheckUrl('code_game_worlds', 'name', $url, $id, $ajax)) {
						if($released = isset($_POST['released']) && trim($_POST['released']) ? t7format::LocalStrtotime(trim($_POST['released'])) : time()) {
							$path = $_SERVER['DOCUMENT_ROOT'] . '/code/games/files/';
							if(isset($_FILES['zip']) && $_FILES['zip']['size'])
								move_uploaded_file($_FILES['zip']['tmp_name'], $path . $url . '.zip');
							if(isset($_FILES['screenshot']) && $_FILES['screenshot']['size'])
								t7file::SaveUploadedImage($_FILES['screenshot'], 'png', [$path . $url . '.png' => 150]);
							$dmzx = isset($_POST['dmzx']) ? +$_POST['dmzx'] : 0;
							$deschtml = t7format::Markdown($desc);
							$sql = 'code_game_worlds set url=?, name=?, released=?, engine=?, descmd=?, deschtml=?, dmzx=?';
							$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
							if($save = $db->prepare($sql))
								if($save->bind_param('ssiissii', $url, $name, $released, $engine, $desc, $deschtml, $dmzx, $id))
									if($save->execute()) {
										$save->close();
										$ajax->Data->url = $url;
										if($id && $url != $_POST['originalurl'] && $_POST['originalurl'] == $originalurl = t7format::NameToUrl($_POST['originalurl'])) {
											if(isset($_FILES['zip']) && $_FILES['zip']['size'])
												unlink($path . $originalurl . '.zip');
											else
												rename($path . $originalurl . '.zip', $path . $url . '.zip');
											if(isset($_FILES['screenshot']) && $_FILES['screenshot']['size'])
												unlink($path . $originalurl . '.png');
											else
												rename($path . $originalurl . '.png', $path . $url . '.png');
										}
									} else
										$ajax->Fail('error saving game world', $save->errno . ' ' . $save->error);
								else
									$ajax->Fail('error binding parameters to save game world', $save->errno . ' ' . $save->error);
							else
								$ajax->Fail('error preparing to save game world', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail('invalid date.');
					}  // CheckUrl logs its own error message
				} else
					$ajax->Fail('zip file and screenshot are required for new game worlds.');
			} else
				$ajax->Fail('name, engine, and desc are required.');
		else
			$ajax->Fail('only the administrator can edit game worlds.  you might need to log in again.');
	}
}
gameworldsApi::Respond();
