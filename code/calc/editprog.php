<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' calculator program - software');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> calculator program</h1>
			<form id=editprog method=post enctype="" v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden name=id value=<?=$id; ?>>
<?php
}
?>
				<label>
					<span class=label>name:</span>
					<span class=field><input name=name maxlength=32 required v-model=name v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input name=url maxlength=32 pattern="[a-z0-9\-\._]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label>
					<span class=label>subject:</span>
					<span class=field><select name=subject v-model=subject>
						<option disabled selected value="">(choose a subject)</option>
<?php
if($sbjs = $db->query('select id, name from code_calc_subject order by name'))
	while($sbj = $sbjs->fetch_object()) {
?>
						<option value=<?=+$sbj->id; ?>><?=htmlspecialchars($sbj->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label>
					<span class=label>model:</span>
					<span class=field><select name=model v-model=model>
						<option disabled selected value="">(choose a calculator model)</option>
<?php
if($mods = $db->query('select id, name from code_calc_model order by name'))
	while($mod = $mods->fetch_object()) {
?>
						<option value=<?=+$mod->id; ?>><?=htmlspecialchars($mod->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=desc required rows="" cols="" v-model=desc></textarea></span>
				</label>
				<label>
					<span class=label>file:</span>
					<span class=field><input name=upload type=file></span>
				</label>
				<label>
					<span class=label>ticalc:</span>
					<span class=field>http://www.ticalc.org/archives/files/fileinfo/{{ticalcPrefix}}<input name=ticalc type=number v-model=ticalc>.html</span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input name=released v-model=released v-on:change=ValidateReleased></span>
				</label>
				<button :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
$html->Close();
