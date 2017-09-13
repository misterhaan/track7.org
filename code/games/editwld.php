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
		case 'load': LoadWorld(); break;
		case 'save': SaveWorld(); break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['ko' => true]);
$html->Open(($id ? 'edit' : 'add') . ' game world - software');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> game world</h1>
			<form id=editwld method=post enctype="" data-bind="submit: Save">
				<label>
					<span class=label>name:</span>
					<span class=field><input maxlength=64 required data-bind="textInput: name"></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input maxlength=32 pattern="[a-z0-9\-\._]+" data-bind="value: url, attr: {placeholder: defaultUrl}"></span>
				</label>
				<label>
					<span class=label>game:</span>
					<span class=field><select data-bind="value: engine">
						<option disabled selected value="">(choose a game engine)</option>
<?php
if($engs = $db->query('select id, name from code_game_engines order by name'))
	while($eng = $engs->fetch_object()) {
?>
						<option value=<?php echo +$eng->id; ?>><?php echo htmlspecialchars($eng->name); ?></option>
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
					<span class=label>zip file:</span>
					<span class=field><input id=zip type=file></span>
				</label>
				<label>
					<span class=label>screenshot:</span>
					<span class=field><input id=screenshot type=file></span>
				</label>
				<label>
					<span class=label>dmzx id:</span>
					<span class=field><input data-bind="value: dmzx"></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input data-bind="value: released"></span>
				</label>
				<button id=save>save</button>
			</form>
<?php
$html->Close();

function LoadWorld() {
	global $ajax, $db;
	if(isset($_GET['id']) && +$_GET['id'])
		if($sel = $db->prepare('select name, url, engine, descmd, dmzx, released from code_game_worlds where id=?')) {
			$id = +$_GET['id'];
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
						$ajax->Fail('error binding game world information result:  ' . $sel->error);
				else
					$ajax->Fail('error executing game world information request:  ' . $sel->error);
			else
				$ajax->Fail('error binding game world id to request:  ' . $sel->error);
		} else
			$ajax->Fail('error preparing to get game world information:  ' . $db->error);
	else
		$ajax->Fail('id is required.');
}

function SaveWorld() {
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
	$engine = +$_POST['engine'];
	if(!$engine)
		$ajax->Data->fieldIssues[] = ['field' => 'engine', 'issue' => 'engine is required.'];
	$desc = trim($_POST['desc']);
	if(!$desc)
		$ajax->Data->fieldIssues[] = ['field' => 'desc', 'issue' => 'description is required.'];
	else
		$deschtml = t7format::Markdown($desc);
	$dmzx = +trim($_POST['dmzx']);
	if(!$dmzx)
		$dmzx = null;
	$released = trim($_POST['released']);
	$released = $released ? t7format::LocalStrtotime($released) : time();

	if(count($ajax->Data->fieldIssues)) {
		$ajax->Fail('at least one field is invalid.');
		if(isset($_FILES['zip']) && $_FILES['zip']['size'])
			@unlink($_FILES['zip']['tmp_name']);
		if(isset($_FILES['screenshot']) && $_FILES['screenshot']['size'])
			@unlink($_FILES['screenshot']['tmp_name']);
	} else {
		if(isset($_FILES['zip']) && $_FILES['zip']['size'])
			move_uploaded_file($_FILES['zip']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $url . '.zip');
		if(isset($_FILES['screenshot']) && $_FILES['screenshot']['size'])
			move_uploaded_file($_FILES['screenshot']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/files/' . $url . '.png');
		$sql = 'code_game_worlds set url=?, name=?, released=?, engine=?, descmd=?, deschtml=?, dmzx=?';
		$sql = $id ? 'update ' . $sql . ' where id=? limit 1' : 'insert into ' . $sql . ', id=?';
		if($save = $db->prepare($sql)) {
			if($save->bind_param('ssiissii', $url, $name, $released, $engine, $desc, $deschtml, $dmzx, $id))
				if($save->execute())
					$save->close();
				else
					$ajax->Fail('error saving game world:  ' . $save->error);
			else
				$ajax->Fail('error binding parameters to save game world:  ' . $save->error);
			$ajax->Data->url = $url;
		} else
			$ajax->Fail('error preparing to save game world:  ' . $db->error);
	}
}

function VerifyUniqueUrl($url, $id) {
	global $ajax, $db;
	if($chk = $db->prepare('select id, name from code_game_worlds where url=? and id!=? limit 1')) {
		if($chk->bind_param('si', $url, $id))
			if($chk->execute())
				if($chk->bind_result($dupid, $dupname)) {
					if($chk->fetch())
						$ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be unique!  already in use by game world named ' . $dupname];
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
