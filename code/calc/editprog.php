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
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'load': LoadProg(); break;
		case 'save': SaveProg(); break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['ko' => true]);
$html->Open(($id ? 'edit' : 'add') . ' calculator program - software');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> calculator program</h1>
			<form id=editprog method=post enctype="" data-bind="submit: Save">
				<label>
					<span class=label>name:</span>
					<span class=field><input maxlength=32 required data-bind="textInput: name"></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input maxlength=32 pattern="[a-z0-9\-\._]+" data-bind="value: url, attr: {placeholder: defaultUrl}"></span>
				</label>
				<label>
					<span class=label>subject:</span>
					<span class=field><select data-bind="value: subject">
						<option disabled selected value="">(choose a subject)</option>
<?php
if($sbjs = $db->query('select id, name from code_calc_subject order by name'))
	while($sbj = $sbjs->fetch_object()) {
?>
						<option value=<?php echo +$sbj->id; ?>><?php echo htmlspecialchars($sbj->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label>
					<span class=label>model:</span>
					<span class=field><select data-bind="value: model">
						<option disabled selected value="">(choose a calculator model)</option>
<?php
if($mods = $db->query('select id, name from code_calc_model order by name'))
	while($mod = $mods->fetch_object()) {
?>
						<option value=<?php echo +$mod->id; ?>><?php echo htmlspecialchars($mod->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea id=desc required rows="" cols="" data-bind="value: desc"></textarea></span>
				</label>
				<label>
					<span class=label>file:</span>
					<span class=field><input id=upload type=file></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input data-bind="value: released"></span>
				</label>
				<button id=save>save</button>
			</form>
<?php

function LoadProg() {
	global $ajax, $db;
	if(isset($_GET['id']) && +$_GET['id'])
		if($sel = $db->prepare('select name, url, subject, model, descmd, released from code_calc_progs where id=?'))
			if($sel->bind_param('i', $id = +$_GET['id']))
				if($sel->execute())
					if($sel->bind_result($name, $url, $subject, $model, $desc, $released))
						if($sel->fetch()) {
							$ajax->Data->name = $name;
							$ajax->Data->url = $url;
							$ajax->Data->subject = $subject;
							$ajax->Data->model = $model;
							$ajax->Data->desc = $desc;
							$ajax->Data->released = t7format::LocalDate('Y-m-d g:i:s a', $released);
						} else
							$ajax->Fail('error fetching program information:  ' . $sel->error);
					else
						$ajax->Fail('error binding program information result:  ' . $sel->error);
				else
					$ajax->Fail('error executing program information request:  ' . $sel->error);
			else
				$ajax->Fail('error binding program id to request:  ' . $sel->error);
		else
			$ajax->Fail('error preparing to get program information:  ' . $db->error);
	else
		$ajax->Fail('id is required.');
}

function SaveProg() {
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
	$subject = +$_POST['subject'];
	if(!$subject)
		$ajax->Data->fieldIssues[] = ['field' => 'subject', 'issue' => 'subject is required.'];
	$model = +$_POST['model'];
	if(!$model)
		$ajax->Data->fieldIssues[] = ['field' => 'model', 'issue' => 'model is required.'];
	$desc = trim($_POST['desc']);
	if(!$desc)
		$ajax->Data->fieldIssues[] = ['field' => 'desc', 'issue' => 'description is required.'];
	else
		$deschtml = t7format::Markdown($desc);
	$released = trim($_POST['released']);
	$released = $released ? t7format::LocalStrtotime($released) : time();

	if(count($ajax->Data->fieldIssues)) {
		$ajax->Fail('at least one field is invalid.');
		if(isset($_FILES['upload']) && $_FILES['upload']['size'])
			@unlink($_FILES['upload']['tmp_name']);
	} else {
		if($uploaded = isset($_FILES['upload']) && $_FILES['upload']['size'])
			move_uploaded_file($_FILES['upload']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $url . '.zip');
		$sql = 'code_calc_progs set url=?, name=?, released=?, subject=?, model=?, descmd=?, deschtml=?';
		$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
		if($save = $db->prepare($sql)) {
			if($save->bind_param('ssiiissi', $url, $name, $released, $subject, $model, $desc, $deschtml, $id))
				if($save->execute())
					$save->close();
				else
					$ajax->Fail('error saving program:  ' . $save->error);
			else
				$ajax->Fail('error binding parameters to save program:  ' . $save->error);
			$ajax->Data->url = $url;
		} else
			$ajax->Fail('error preparing to save script:  ' . $db->error);
	}
}

function VerifyUniqueUrl($url, $id) {
	global $ajax, $db;
	if($chk = $db->prepare('select id, name from code_calc_progs where url=? and id!=? limit 1')) {
		if($chk->bind_param('si', $url, $id))
			if($chk->execute())
				if($chk->bind_result($dupid, $dupname)) {
					if($chk->fetch())
						$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be unique!  already in use by program named ' . $dupname];
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
