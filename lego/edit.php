<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('lego model not found');
?>
			<h1>404 lego model not found</h1>

			<p>
				sorry, we don’t seem to have a lego model by that name.  try picking one
				from <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' lego model');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> lego model</h1>
			<form id=editlego v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden name=id id=legoid value="<?=$id; ?>">
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input name=title maxlength=32 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-\._]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label title="upload a 3d rendered image" :class="{multiline: image}">
					<span class=label>image:</span>
					<span class=field>
						<input type=file name=image accept=".png, image/png" v-on:change=CacheImage :class="{hidden: image}">
						<img class="art preview" v-if=image :src=image>
					</span>
				</label>
				<!-- ldraw data file (zipped) -->
				<label title="upload ldraw data file (zipped)">
					<span class=label>ldraw zip:</span>
					<span class=field>
						<input type=file name=ldraw accept=".zip, application/zip, application/x-zip, application/x-zip-compressed">
					</span>
				</label>
				<!-- step-by-step images (zipped) -->
				<label title="upload step-by-step instruction images (zipped)">
					<span class=label>instructions:</span>
					<span class=field>
						<input type=file name=instructions accept=".zip, application/zip, application/x-zip, application/x-zip-compressed">
					</span>
				</label>
				<label title="number of pieces in this model">
					<span class=label>pieces:</span>
					<span class=field>
						<input name=pieces type=number min=3 max=9999 maxlength=4 step=1 v-model=pieces>
					</span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=descmd v-model=descmd></textarea></span>
				</label>
				<button id=save :disabled=saving :class="{working: saving}">save</button>
				<p v-if=id><img class="art preview" :src="url ? 'data/' + url + '.png' : ''"></p>
			</form>

<?php
$html->Close();
