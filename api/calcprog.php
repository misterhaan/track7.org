<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for calcprog api requests.
 * @author misterhaan
 */
class calcprogApi extends t7api {
	/**
	 * write out the documentation for the validate api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>
				get calculator program information for editing.  only available to
				admin.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>calculator program id to load for editing.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to a calculator program or add a new calculator program.
				only available to admin.  accepts file upload named "upload" which is
				required for new calculator programs and will replace the existing file
				when updating existing calculator programs.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					calculator program id to save.  will add a new calculator program if
					empty or missing.
				</dd>
			</dl>

<?php
	}

	/**
	 * get calculator program information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && +$_GET['id'])
				if($sel = $db->prepare('select name, url, subject, model, descmd, ticalc, released from code_calc_progs where id=?')) {
					$id = +$_GET['id'];
					if($sel->bind_param('i', $id))
						if($sel->execute())
							if($sel->bind_result($name, $url, $subject, $model, $desc, $ticalc, $released))
								if($sel->fetch()) {
									$ajax->Data->name = $name;
									$ajax->Data->url = $url;
									$ajax->Data->subject = $subject;
									$ajax->Data->model = $model;
									$ajax->Data->desc = $desc;
									$ajax->Data->ticalc = $ticalc;
									$ajax->Data->released = t7format::LocalDate('Y-m-d g:i:s a', $released);
								} else
									$ajax->Fail('error fetching program information', $sel->errno . ' ' . $sel->error);
							else
								$ajax->Fail('error binding program information result', $sel->errno . ' ' . $sel->error);
						else
							$ajax->Fail('error executing program information request', $sel->errno . ' ' . $sel->error);
					else
						$ajax->Fail('error binding program id to request', $sel->errno . ' ' . $sel->error);
				} else
					$ajax->Fail('error preparing to get program information', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('id is required.');
		else
			$ajax->Fail('only the administrator can edit calculator programs.  you might need to log in again.');
	}

	/**
	 * save changes to web code or add a new web code.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['name']) && ($name = trim($_POST['name'])) && isset($_POST['subject']) && ($subject = +$_POST['subject']) && isset($_POST['model']) && ($model = +$_POST['model']) && isset($_POST['desc']) && ($desc = trim($_POST['desc']))) {
				$id = isset($_POST['id']) ? +$_POST['id'] : 0;
				$url = t7format::NameToUrl(isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : $name);
				if(self::CheckUrl('code_calc_progs', 'name', $url, $id, $ajax)) {
					if($released = isset($_POST['released']) && trim($_POST['released']) ? t7format::LocalStrtotime(trim($_POST['released'])) : time()) {
						if($id || isset($_FILES['upload']) && $_FILES['upload']['size']) {
							$deschtml = t7format::Markdown($desc);
							$ticalc = isset($_POST['ticalc']) ? trim($_POST['ticalc']) : '';
							$path = $_SERVER['DOCUMENT_ROOT'] . '/code/calc/files/';
							$filepath = $path . $url . '.zip';
							if(isset($_FILES['upload']) && $_FILES['upload']['size'])
								move_uploaded_file($_FILES['upload']['tmp_name'], $filepath);
							$sql = 'code_calc_progs set url=?, name=?, released=?, subject=?, model=?, descmd=?, deschtml=?, ticalc=?';
							$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
							if($save = $db->prepare($sql))
								if($save->bind_param('ssiiissii', $url, $name, $released, $subject, $model, $desc, $deschtml, $ticalc, $id))
									if($save->execute()) {
										$save->close();
										$ajax->Data->url = $url;
										if($id && isset($_POST['originalurl']) && $_POST['originalurl'] == ($originalurl = t7format::NameToUrl($_POST['originalurl'])))
											if(isset($_FILES['upload']) && $_FILES['upload']['size'])
												unlink($path . $originalurl . '.zip');
											else
												rename($path . $originalurl . '.zip', $path . $url . '.zip');
									} else
										$ajax->Fail('error saving calculator program', $save->errno . ' ' . $save->error);
								else
									$ajax->Fail('error binding data to save calculator program', $save->errno . ' ' . $save->error);
							else
								$ajax->Fail('error preparing to save calculator program', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail('new calculator programs need to provide a file.');
					} else
						$ajax->Fail('invalid date');
				}  // CheckUrl logs its own failure
			} else
				$ajax->Fail('name, subject, model, desc are required.');
		else
			$ajax->Fail('only the administrator can edit calculator programs.  you might need to log in again.');
	}
}
calcprogApi::Respond();
