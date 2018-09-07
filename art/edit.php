<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('art not found');
?>
			<h1>404 art not found</h1>

			<p>
				sorry, we don’t seem to have any art by that name.  try picking one from
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' art');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> art</h1>
			<form id=editart v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden id=artid name=id value="<?=$id ?>">
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input name=title maxlength=32 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-_]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label class=multiline title="upload the art">
					<span class=label>art:</span>
					<span class=field>
						<input type=file name=art accept=".jpg, .jpeg, .png, image/jpeg, image/jpg, image/png" v-on:change=CacheArt :class="{hidden: art}">
						<img class="art preview" v-if=art :src=art>
					</span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=descmd v-model=descmd></textarea></span>
				</label>
				<label>
					<span class=label>deviantart:</span>
					<span class=field>https://deviantart.com/art/<input name=deviation maxlength=64 v-model=deviation></span>
				</label>
<?php
$html->ShowTagsField('art');
?>
				<button :disabled=saving :class="{working: saving}">save</button>
				<p v-if=id><img class="art preview" :src="url ? 'img/' + url + '.' + ext : ''"></p>
			</form>
<?php
$html->Close();
