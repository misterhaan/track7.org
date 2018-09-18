<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('application not found - software');
?>
			<h1>404 application not found</h1>

			<p>
				sorry, we don’t seem to have an application by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all applications</a>.
			</p>
<?php
	$html->Close();
	die;
}

if(isset($_GET['app']))
	if($app = $db->query('select id, name, url from code_vs_applications where id=\'' . +$_GET['app'] . '\' limit 1'))
		if($app = $app->fetch_object()) {
			$html = new t7html(['vue' => true]);
			$html->Open('add release - ' . htmlspecialchars($app->name));
?>
			<h1>add release:  <?=htmlspecialchars($app->name); ?></h1>
			<form id=addrel method=post enctype="multipart/form-data" v-on:submit.prevent=Save>
				<input type=hidden name=app value=<?=$app->id; ?>>
				<label>
					<span class=label>version:</span>
					<span class=field><input name=version maxlength=10 pattern="[0-9]+(\.[0-9]+){0,2}" required v-model=version v-on:change=ValidateVersion></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input name=released v-model=released v-on:change=ValidateReleased></span>
				</label>
				<label>
					<span class=label>language:</span>
					<span class=field><select name=language>
<?php
			if($langs = $db->query('select id, name from code_vs_lang order by name'))
				while($lang = $langs->fetch_object()) {
?>
						<option value=<?=$lang->id; ?>><?=$lang->name; ?></option>
<?php
				}
?>
					</select></span>
				</label>
				<label>
					<span class=label>.net:</span>
					<span class=field><select name=dotnet>
<?php
			if($dotnets = $db->query('select id, version from code_vs_dotnet order by id desc'))
				while($dotnet = $dotnets->fetch_object()) {
?>
						<option value="<?=$dotnet->id; ?>"><?=$dotnet->version; ?></option>
<?php
				}
?>
						<option value="">n/a</option>
					</select></span>
				</label>
				<label>
					<span class=label>studio:</span>
					<span class=field><select name=studio>
<?php
			if($studios = $db->query('select version, name from code_vs_studio order by version desc'))
				while($studio = $studios->fetch_object()) {
?>
						<option value=<?=$studio->version; ?>><?=$studio->name; ?></option>
<?php
				}
?>
					</select></span>
				</label>
				<label>
					<span class=label>bin url:</span>
					<span class=field><input name=binurl maxlength=128></span>
				</label>
				<label>
					<span class=label>binary:</span>
					<span class=field><input type=file name=binfile></span>
				</label>
				<label>
					<span class=label>bin32 url:</span>
					<span class=field><input name=bin32url maxlength=128></span>
				</label>
				<label>
					<span class=label>binary32:</span>
					<span class=field><input type=file name=bin32file></span>
				</label>
				<label>
					<span class=label>src url:</span>
					<span class=field><input name=srcurl maxlength=128></span>
				</label>
				<label>
					<span class=label>source:</span>
					<span class=field><input type=file name=srcfile></span>
				</label>
				<label class=multiline>
					<span class=label>changes:</span>
					<span class=field><textarea name=changelog></textarea></span>
				</label>
				<button :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
			$html->Close();
		} else
			;  // app not found
	else
		;  // db error
else
	;  // app not provided
