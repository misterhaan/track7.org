<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	if(isset($_GET['ajax'])) {
		$ajax = new t7ajax();
		$ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
		$ajax->Send();
		die;
	}
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('web script not found - software');
?>
			<h1>404 web script not found</h1>

			<p>
				sorry, we don’t seem to have a web script by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all web scripts</a>.
			</p>
<?php
	$html->Close();
	die;
}

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'load': LoadScript(); break;
		case 'save': SaveScript(); break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['ko' => true]);
$html->Open(($id ? 'edit' : 'add') . ' web script - software');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> web script</h1>
			<form id=editscr method=post enctype="" data-bind="submit: Save">
				<label>
					<span class=label>name:</span>
					<span class=field><input maxlength=32 required data-bind="textInput: name"></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input maxlength=32 pattern="[a-z0-9\-\._]+" data-bind="value: url, attr: {placeholder: defaultUrl}"></span>
				</label>
				<label>
					<span class=label>type:</span>
					<span class=field><select data-bind="value: usetype">
						<option disabled selected value="">(choose a script type)</option>
<?php
if($types = $db->query('select id, name from code_web_usetype order by name'))
	while($type = $types->fetch_object()) {
?>
						<option value=<?php echo +$type->id; ?>><?php echo htmlspecialchars($type->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea id=desc required rows="" cols="" data-bind="value: desc"></textarea></span>
				</label>
				<label class=multiline>
					<span class=label>instructions:</span>
					<span class=field><textarea id=instr rows="" cols="" data-bind="value: instr"></textarea></span>
				</label>
				<fieldset class=selectafield>
					<div>
						<label class=label><input type=radio name=filelocation value=upload data-bind="checked: filelocation">upload:</label>
						<label class=field><input id=upload type=file></label>
					</div>
					<div>
						<label class=label><input type=radio name=filelocation value=link data-bind="checked: filelocation">link:</label>
						<label class=field><input type=url data-bind="value: link" maxlength=64></label>
					</div>
				</fieldset>
				<fieldset class=checkboxes>
					<legend>requires:</legend>
					<span class=field>
<?php
if($reqs = $db->query('select id, name from code_web_reqinfo order by name'))
	while($req = $reqs->fetch_object()) {
?>
						<label class=checkbox>
							<input type=checkbox value=<?php echo +$req->id; ?> data-bind="checked: reqs">
							<?php echo htmlspecialchars($req->name); ?>
						</label>
<?php
	}
?>
					</span>
				</fieldset>
				<label>
					<span class=label>github:</span>
					<span class=field>https://github.com/misterhaan/<input maxlength=16 data-bind="value: github"></span>
				</label>
				<label>
					<span class=label>auwiki:</span>
					<span class=field>http://wiki.track7.org/<input maxlength=32 data-bind="value: wiki"></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input data-bind="value: released"></span>
				</label>
				<button id=save>save</button>
			</form>

<?php
$html->Close();

function LoadScript() {
	global $ajax, $db;
	if(isset($_GET['id']) && +$_GET['id'])
		if($sel = $db->prepare('select s.name, s.url, s.usetype, s.descmd, s.instmd, s.download, group_concat(r.req separator \',\') as reqslist, s.github, s.wiki, s.released from code_web_scripts as s left join code_web_requirements as r on r.script=s.id where s.id=? group by s.id'))
			if($sel->bind_param('i', $id = +$_GET['id']))
				if($sel->execute())
					if($sel->bind_result($name, $url, $usetype, $desc, $instr, $link, $reqslist, $github, $wiki, $released))
						if($sel->fetch()) {
							$ajax->Data->name = $name;
							$ajax->Data->url = $url;
							$ajax->Data->usetype = $usetype;
							$ajax->Data->desc = $desc;
							$ajax->Data->instr = $instr;
							$ajax->Data->link = $link;
							$ajax->Data->reqslist = $reqslist;
							$ajax->Data->github = $github;
							$ajax->Data->wiki = $wiki;
							$ajax->Data->released = t7format::LocalDate('Y-m-d g:i:s a', $released);
						} else
							$ajax->Fail('error fetching script information:  ' . $sel->error);
					else
						$ajax->Fail('error binding script information result:  ' . $sel->error);
				else
					$ajax->Fail('error executing script information request:  ' . $sel->error);
			else
				$ajax->Fail('error binding script id to request:  ' . $sel->error);
		else
			$ajax->Fail('error preparing to get script information:  ' . $db->error);
	else
		$ajax->Fail('id is required.');
}

function SaveScript() {
	global $ajax, $db;
	$ajax->Data->fieldIssues = [];
	$id = isset($_POST['id']) ? +$_POST['id'] : null;
	$name = trim($_POST['name']);
	if(!$name)
		$ajax->Data->fieldIssues[] = ['field' => 'name', 'issue' => 'name is required'];
	$url = trim($_POST['url']);
	if(!$url)
		$url = preg_replace('/[^a-z0-9\.\-_]*/', '', str_replace(' ', '-', strtolower($name)));
	if(!preg_match('/^[a-z0-9\.\-_]{1,32}$/', $url))
		$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be 1 - 32 lowercase letters, numbers, dashes, and periods.'];
	else
		VerifyUniqueUrl($url, $id);
	$usetype = +$_POST['usetype'];
	VerifyUseType($usetype);
	$desc = trim($_POST['desc']);
	if(!$desc)
		$ajax->Data->fieldIssues[] = ['field' => 'desc', 'issue' => 'description is required.'];
	else
		$deschtml = t7format::Markdown($desc);
	$instr = trim($_POST['instr']);
	$intsrhtml = $instr ? t7format::Markdown($instr) : '';
	if(isset($_POST['link'])) {
		$download = trim($_POST['link']);
		if(!$download)
			$ajax->Data->fieldIssues[] = ['field' => 'link', 'issue' => 'link cannot be blank unless a file is uploaded.'];
	}
	$reqs = $_POST['reqslist'] ? explode(',', $_POST['reqslist']) : [];
	$github = trim($_POST['github']);
	$wiki = trim($_POST['wiki']);
	$released = trim($_POST['released']);
	$released = $released ? t7format::LocalStrtotime($released) : time();

	if(count($ajax->Data->fieldIssues)) {
		$ajax->Fail('at least one field is invalid.');
		if(isset($_FILES['upload']) && $_FILES['upload']['size'])
			@unlink($_FILES['upload']['tmp_name']);
	} else {
		if($uploaded = isset($_FILES['upload']) && $_FILES['upload']['size']) {
			move_uploaded_file($_FILES['upload']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $url . '.zip');
			$download = '';
		}
		if(isset($download) && $download || file_exists($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $url . '.zip')) {
			$sql = 'code_web_scripts set url=?, name=?, released=?, usetype=?, download=?, github=?, wiki=?, descmd=?, deschtml=?, instmd=?, insthtml=?';
			$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
			if($save = $db->prepare($sql)) {
				if($save->bind_param('ssiisssssssi', $url, $name, $released, $usetype, $download, $github, $wiki, $desc, $deschtml, $instr, $intsrhtml, $id))
					if($save->execute()) {
						if(!$id) {
							$id = $save->insert_id;
							t7send::Tweet('new web script: ' . $name, 'http://' . $_SERVER['HTTP_HOST'] . '/code/web/' . $url);
						}
						$save->close();
						$ajax->Data->url = $url;
						if($remreq = $db->prepare('delete from code_web_requirements where script=?'))
							if($remreq->bind_param('i', $id))
								if($remreq->execute()) {
									$remreq->close();
									if($addreq = $db->prepare('insert into code_web_requirements (script, req) values (?, ?)'))
										if($addreq->bind_param('ii', $id, $reqid)) {
											foreach($reqs as $reqid)
												if(!$addreq->execute())
													$ajax->Fail('error saving requirement ' . $reqid . ':  ' . $addreq->error);
										} else
											$ajax->Fail('error binding requirements:  ' . $addreq->error);
									else
										$ajax->Fail('error preparing to save requirements:  ' . $db->error);
								} else
									$ajax->Fail('error removing old requirements:  ' . $remreq->error);
							else
								$ajax->Fail('error binding script id to remove old requirements:  ' . $remreq->error);
						else
							$ajax->Fail('error preparing to remove old requirements:  ' . $db->error);
					} else
						$ajax->Fail('error saving script:  ' . $save->error);
				else
					$ajax->Fail('error binding parameters to save script:  ' . $save->error);
			} else
				$ajax->Fail('error preparing to save script:  ' . $db->error);
		} else
			$ajax->Fail('scripts must have either a download link or an uploaded file.');
	}
}

function VerifyUniqueUrl($url) {
	global $ajax, $db;
	if($chk = $db->prepare('select id, name from code_web_scripts where url=? and id!=? limit 1')) {
		if($chk->bind_param('si', $url, $id))
			if($chk->execute())
				if($chk->bind_result($dupid, $dupname)) {
					if($chk->fetch())
						$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be unique!  already in use by script named ' . $dupname];
				} else
					$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'error binding result after verifying unique url:  ' . $chk->error];
			else
				$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'error executing verification of unique url:  ' . $chk->error];
		else
			$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'error binding parameter for verifying unique url:  ' . $chk->error];
		$chk->close();
	} else
		$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'error preparing to verify uniqueness of url:  ' . $db->error];
}

function VerifyUseType($usetype) {
	global $ajax, $db;
	if($usetype)
		if($chk = $db->prepare('select id from code_web_usetype where id=? limit 1')) {
			if($chk->bind_param('i', $usetype))
				if($chk->execute())
					if($chk->bind_result($id)) {
						if($chk->fetch())
							;  // found one; no error
						else
							$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'selected type does not exist'];
					} else
						$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'error binding result of type verification:  ' . $chk->error];
				else
					$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'error executing type verification:  ' . $chk->error];
			else
				$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'error binding parameter for verifying type:  ' . $chk->error];
			$chk->close();
		} else
			$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'error preparing to validate type:  ' . $db->error];
	else
		$ajax->Data->fieldIssues[] = ['field' => 'usetype', 'issue' => 'type is required.'];
}
