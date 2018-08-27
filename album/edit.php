<?php
define('MAX_PHOTO_SIZE', 800);
define('MAX_THUMB_SIZE', 150);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('photo not found - blog');
?>
			<h1>404 photo not found</h1>

			<p>
				sorry, we don’t seem to have a photo by that name.  try picking one from
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' photo - album');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> photo</h1>
			<form id=editphoto v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden id=photoid name=id value="<?php echo $id; ?>">
<?php
}
?>
				<label>
					<span class=label>caption:</span>
					<span class=field><input name=caption maxlength=32 required v-model=caption v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input name=url maxlength=32 pattern="[a-z0-9\-_]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label title="youtube video id if this photo is a video (unique part of the video url)">
					<span class=label>youtube:</span>
					<span class=field><input name=youtube maxlength=32 v-model=youtube></span>
				</label>
				<label title="upload the photo, or a thumbnail for a video">
					<span class=label>photo:</span>
					<span class=field>
						<input type=file name=photo accept="image/jpeg, image/jpg" v-on:change=CachePhoto :class="{hidden: photo}">
						<img class="photo preview" v-if=photo :src=photo>
					</span>
				</label>
				<label class=multiline>
					<span class=label>story:</span>
					<span class=field><textarea name=storymd v-model=storymd></textarea></span>
				</label>
				<label>
					<span class=label>taken:</span>
					<span class=field><input name=taken v-model=taken v-on:change=ValidateTaken></span>
				</label>
				<label>
					<span class=label>year:</span>
					<span class=field><input name=year pattern="[0-9]{4}" maxlength=4 v-model=year></span>
				</label>
<?php
$html->ShowTagsField('photos');
?>
				<button :disabled=saving :class="{working: saving}">save</button>
				<p v-if=id><img class=photo :src="url ? 'photos/' + url + '.jpeg' : ''"></p>
			</form>
<?php
$html->Close();
