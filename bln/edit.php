<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('entry not found - blog');
?>
			<h1>404 blog entry not found</h1>

			<p>
				sorry, we don’t seem to have a blog entry by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all blog entries</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' entry - blog');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> entry</h1>
			<form id=editentry data-entryid="<?php echo $id; ?>" v-on:submit.prevent=Save>
				<label>
					<span class=label>title:</span>
					<span class=field><input id=title maxlength=128 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url maxlength=32 pattern="[a-z0-9\.\-_]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label class=multiline>
					<span class=label>entry:</span>
					<span class=field><textarea id=content required rows="" cols="" v-model=content></textarea></span>
				</label>
<?php
$html->ShowTagsField('blog');
?>
				<button id=save :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
$html->Close();
