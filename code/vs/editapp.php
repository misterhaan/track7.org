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
				<a href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">all applications</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' application - software');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> application</h1>
			<form id=editapp method=post enctype="" v-on:submit.prevent=Save>
<?php
if($id) {
?>
				<input type=hidden id=appid name=id value="<?=$id; ?>">
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
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=desc required rows="" cols="" v-model=desc></textarea></span>
				</label>
				<label>
					<span class=label>icon:</span>
					<span class=field><input type=file name=icon accept=".png, image/png" v-on:change=CacheIcon :class="{hidden: icon}"><img class="icon preview" :src=icon v-if=icon></span>
				</label>
				<label>
					<span class=label>github:</span>
					<span class=field>https://github.com/misterhaan/<input name=github maxlength=16 v-model=github></span>
				</label>
				<label>
					<span class=label>auwiki:</span>
					<span class=field>https://wiki.track7.org/<input name=wiki maxlength=32 v-model=wiki></span>
				</label>
				<button :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
$html->Close();
