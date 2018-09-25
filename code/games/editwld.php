<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' game world - software');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> game world</h1>
			<form id=editwld method=post enctype="" v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden name=id value=<?=$id; ?>>
<?php
}
?>
				<label>
					<span class=label>name:</span>
					<span class=field><input name=name maxlength=64 required v-model=name v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input name=url maxlength=32 pattern="[a-z0-9\-\._]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label>
					<span class=label>game:</span>
					<span class=field><select name=engine v-model=engine>
						<option disabled selected value="">(choose a game engine)</option>
<?php
if($engs = $db->query('select id, name from code_game_engines order by name'))
	while($eng = $engs->fetch_object()) {
?>
						<option value=<?=+$eng->id; ?>><?=htmlspecialchars($eng->name); ?></option>
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
					<span class=label>zip file:</span>
					<span class=field><input name=zip type=file></span>
				</label>
				<label>
					<span class=label>screenshot:</span>
					<span class=field><input name=screenshot type=file v-on:change=CacheScreenshot :class="{hidden: screenshot}"><img class=preview v-if=screenshot :src=screenshot></span>
				</label>
				<label>
					<span class=label>dmzx id:</span>
					<span class=field>http://vault.digitalmzx.net/show.php?id=<input name=dmzx v-model=dmzx></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input name=released v-model=released v-on:change=ValidateReleased></span>
				</label>
				<button :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
$html->Close();
