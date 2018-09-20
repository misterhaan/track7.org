<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
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

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' web script - software');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> web script</h1>
			<form id=editscr method=post enctype="" v-on:submit.prevent=Save>
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
					<span class=label>type:</span>
					<span class=field><select name=usetype v-model=usetype>
						<option disabled selected value="">(choose a script type)</option>
<?php
if($types = $db->query('select id, name from code_web_usetype order by name'))
	while($type = $types->fetch_object()) {
?>
						<option value=<?=+$type->id; ?>><?=htmlspecialchars($type->name); ?></option>
<?php
	}
?>
					</select></span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=desc required rows="" cols="" v-model=desc></textarea></span>
				</label>
				<label class=multiline>
					<span class=label>instructions:</span>
					<span class=field><textarea name=instr rows="" cols="" v-model=instr></textarea></span>
				</label>
				<fieldset class=selectafield>
					<div>
						<label class=label><input type=radio value=upload v-model=filelocation>upload:</label>
						<label class=field><input :disabled="filelocation != 'upload'" name=upload type=file></label>
					</div>
					<div>
						<label class=label><input type=radio value=link v-model=filelocation>link:</label>
						<label class=field><input name=link :disabled="filelocation != 'link'" type=url v-model=link maxlength=64></label>
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
							<input name=reqs[] type=checkbox value=<?=+$req->id; ?> v-model=reqs>
							<?=htmlspecialchars($req->name); ?>
						</label>
<?php
	}
?>
					</span>
				</fieldset>
				<label>
					<span class=label>github:</span>
					<span class=field>https://github.com/misterhaan/<input name=github maxlength=16 v-model=github></span>
				</label>
				<label>
					<span class=label>auwiki:</span>
					<span class=field>https://wiki.track7.org/<input name=wiki maxlength=32 v-model=wiki></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input name=released v-model=released v-on:change=ValidateReleased></span>
				</label>
				<button :disabled=saving :class="{working: saving}">save</button>
			</form>

<?php
$html->Close();
