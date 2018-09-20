<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for webcode api requests.
 * @author misterhaan
 */
class webcodeApi extends t7api {
	/**
	 * write out the documentation for the webcode api controller.  the page
	 * is already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getedit>get edit</h2>
			<p>get web code information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>web code id to load for editing.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to web code or add new web code.  only available to admin.
				accepts file upload named "upload" which is required for new web code
				that doesn’t provide a link.
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					web code id to save.  will add new web code if empty or missing.
				</dd>
				<dt>name</dt>
				<dd>web code name as plain text.  required.</dd>
				<dt>url</dt>
				<dd>
					url portion specific to this web code.  will be set from name if
					empty or missing.
				</dd>
				<dt>usetype</dt>
				<dd>type of script.  required.</dd>
				<dt>desc</dt>
				<dd>description of the web code in markdown format.  required.</dd>
				<dt>instr</dt>
				<dd>instructions for the web code in markdown format.  required.</dd>
				<dt>link</dt>
				<dd>
					url where this web code can be found.  required for new web code
					unless upload file is provided.
				</dd>
				<dt>reqlist</dt>
				<dd>comma-separated list of requirement ids for the web code.</dd>
				<dt>github</dt>
				<dd>
					url portion specific to this web code on github.  optional; will not
					link to github unless present.
				</dd>
				<dt>wiki</dt>
				<dd>
					url portion specific to this web code on auwiki.  optional; will not
					link to auwiki unless present.
				</dd>
				<dt>originalurl</dt>
				<dd>
					when editing existing web code, this value is compared against url.
					if they’re different, the file gets renamed.
				</dd>
			</dl>

<?php
	}

	/**
	 * get web code information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $id = +$_GET['id'])
				if($sel = $db->prepare('select s.name, s.url, s.usetype, s.descmd, s.instmd, s.download, group_concat(r.req separator \',\') as reqslist, s.github, s.wiki, s.released from code_web_scripts as s left join code_web_requirements as r on r.script=s.id where s.id=? group by s.id'))
					if($sel->bind_param('i', $id))
						if($sel->execute())
							if($sel->bind_result($name, $url, $usetype, $desc, $instr, $link, $reqslist, $github, $wiki, $released))
								if($sel->fetch()) {
									$ajax->Data->name = $name;
									$ajax->Data->url = $url;
									$ajax->Data->usetype = $usetype;
									$ajax->Data->desc = $desc;
									$ajax->Data->instr = $instr;
									$ajax->Data->link = $link;
									$ajax->Data->reqs = explode(',', $reqslist);
									$ajax->Data->github = $github;
									$ajax->Data->wiki = $wiki;
									$ajax->Data->released = t7format::LocalDate('Y-m-d g:i:s a', $released);
								} else
									$ajax->Fail('cannot find web code.');
							else
								$ajax->Fail('error binding web code result', $sel->errno . ' ' . $sel->error);
						else
							$ajax->Fail('error executing web code information request', $sel->errno . ' ' . $sel->error);
					else
						$ajax->Fail('error binding web code id to request', $sel->errno . ' ' . $sel->error);
				else
					$ajax->Fail('error preparing to get web code information', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit web code.');
		else
			$ajax->Fail('only the administrator can edit web code.  you might need to log in again.');
	}

	/**
	 * save changes to web code or add a new web code.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['name']) && ($name = trim($_POST['name'])) && isset($_POST['usetype']) && ($usetype = +$_POST['usetype']) && isset($_POST['desc']) && ($descmd = trim($_POST['desc']))) {
				$id = isset($_POST['id']) ? +$_POST['id'] : 0;
				$link = isset($_POST['link']) ? trim($_POST['link']) : '';
				$originalurl = isset($_POST['originalurl']) ? t7format::NameToUrl(trim($_POST['originalurl'])) : '';
				$originalfile = $originalurl ? $_SERVER['DOCUMENT_ROOT'] . '/code/web/files/' . $originalurl . '.zip' : '';
				if($id && $originalurl && file_exists($originalfile) || $link || isset($_FILES['upload']) && $_FILES['upload']['size']) {
					$url = t7format::NameToUrl(isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : $name);
					if(self::CheckUrl('code_web_scripts', 'name', $url, $id, $ajax)) {
						if($released = isset($_POST['released']) && trim($_POST['released']) ? t7format::LocalStrtotime(trim($_POST['released'])) : time()) {
							$deschtml = t7format::Markdown($descmd);
							$instrmd = isset($_POST['instr']) ? trim($_POST['instr']) : '';
							$instrhtml = $instrmd ? t7format::Markdown($instrmd) : '';
							$github = isset($_POST['github']) ? trim($_POST['github']) : '';
							$wiki = isset($_POST['wiki']) ? trim($_POST['wiki']) : '';
							$filename = $_SERVER['DOCUMENT_ROOT'] . '/code/web/files/' . $url . '.zip';
							if(isset($_FILES['upload']) && $_FILES['upload']['size'])
								move_uploaded_file($_FILES['upload']['tmp_name'], $filename);
							elseif($link && file_exists($filename))
								unlink($filename);
							$sql = 'code_web_scripts set url=?, name=?, released=?, usetype=?, download=?, github=?, wiki=?, descmd=?, deschtml=?, instmd=?, insthtml=?';
							$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
							if($save = $db->prepare($sql))
								if($save->bind_param('ssiisssssssi', $url, $name, $released, $usetype, $link, $github, $wiki, $descmd, $deschtml, $instrmd, $intsrhtml, $id))
									if($save->execute()) {
										if(!$id) {
											$id = $save->insert_id;
											t7send::Tweet('new web script: ' . $name, t7format::FullUrl('/code/web/' . $url));
										} elseif(!$link && $originalurl && $originalurl != $url)
											rename($originalfile, $filename);
										$save->close();
										if(self::UpdateRequirements($id))
											$ajax->Data->url = $url;
									} else
										$ajax->Fail('database error saving web code', $save->errno . ' ' . $save->error);
								else
									$ajax->Fail('database error binding web code parameters', $save->errno . ' ' . $save->error);
							else
								$ajax->Fail('database error preparing to save web code', $db->errno . ' ' . $db->error);
						} else
							$ajax->Fail('invalid date');
					}  // CheckUrl logs its own failures
				} else
					$ajax->Fail('web code needs either a download link or an uploaded file.');
			} else
				$ajax->Fail('name, usetype, and desc are required.');
		else
			$ajax->Fail('only the administrator can edit web code.  you might need to log in again.');
	}

	/**
	 * wipe and reset requirements for a web code script.
	 * @param int $id web code script id
	 * @return boolean true if successful
	 */
	private static function UpdateRequirements($id) {
		global $db;
		if($remreq = $db->prepare('delete from code_web_requirements where script=?'))
			if($remreq->bind_param('i', $id))
				if($remreq->execute()) {
					$remreq->close();
					if(isset($_POST['reqs']) && count($_POST['reqs']))
						if($addreq = $db->prepare('insert into code_web_requirements (script, req) values (?, ?)'))
							if($addreq->bind_param('ii', $id, $reqid)) {
								foreach($_POST['reqs'] as $reqid) {
									$reqid = +$reqid;
									if(!$addreq->execute())
										$ajax->Fail('error saving requirement ' . $reqid, $addreq->errno . ' ' . $addreq->error);
								}
								$addreq->close();
							} else
								$ajax->Fail('database error binding parameters to save requirements', $addreq->errno . ' ' . $addreq->error);
						else
							$ajax->Fail('database error preparing to save requirements', $db->errno . ' ' . $db->error);
				} else
					$ajax->Fail('database error removing old requirements', $remreq->errno . ' ' . $remreq->error);
			else
				$ajax->Fail('database error binding web code id for removing requirements', $remreq->errno . ' ' . $remreq->error);
		else
			$ajax->Fail('database error preparing to remove old requirements', $db->errno . ' ' . $db->error);
		return !$ajax->Data->fail;
	}
}
webcodeApi::Respond();
