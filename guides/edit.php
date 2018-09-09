<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	// this page is only for admin, so try to view the guide
	if(isset($_GET['url']))
		if(isset($_GET['tag']))
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $_GET['tag'] . '/' . $_GET['url']));
		else
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $_GET['url']));
	else
		header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' guide');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> guide</h1>
			<form id=editguide<?php if($id) echo ' data-id="' . $id . '"'; ?> v-on:submit.prevent=Save>
				<label title="title of the guide (for display)">
					<span class=label>title:</span>
					<span class=field><input id=title maxlength=128 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label title="unique portion of guide url (alphanumeric with dots, dashes, and underscores)">
					<span class=label>url:</span>
					<span class=field><input id=url maxlength=32 pattern="[a-z0-9\-\._]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label class=multiline title="introduction to or summary of the guide (use markdown)">
					<span class=label>summary:</span>
					<span class=field><textarea required rows="" cols="" v-model=summary></textarea></span>
				</label>
				<label title="guide difficulty level">
					<span class=label>level:</span>
					<span class=field><select v-model=level><option>beginner</option><option>intermediate</option><option>advanced</option></select></span>
				</label>
<?php
$html->ShowTagsField('guide');
?>
				<fieldset v-for="(page, index) of pages">
					<legend>chapter {{index + 1}}</legend>
					<a class="action up" href="#moveup" title="move this chapter earlier" v-if=index v-on:click.prevent=MovePageUp(page)></a>
					<a class="action down" href="#movedown" title="move this chapter later" v-if="index < pages.length - 1" v-on:click.prevent=MovePageDown(page)></a>
					<a class="action del" href="#del" title="remove this chapter" v-on:click.prevent=RemovePage(page)></a>
					<label :title="'heading for chapter ' + (index + 1)">
						<span class=label>heading:</span>
						<span class=field><input maxlength=128 required v-model=page.heading></span>
					</label>
					<label class=multiline :title="'content for chapter ' + (index + 1) + ' (use markdown)'">
						<span class=label>content:</span>
						<span class=field><textarea required rows="" cols="" v-model=page.markdown></textarea></span>
					</label>
				</fieldset>
				<label>
					<span class=label></span>
					<span class=field><a class="action new" href="#addpage" title="add a new blank chapter to the end" v-on:click.prevent=AddPage>add chapter</a></span>
				</label>
				<label v-if="status == 'published'">
					<span class=label></span>
					<span class=field><span><input type=checkbox v-model=correctionsOnly> this edit is formatting / spelling / grammar only</span></span>
				</label>
				<button class=save>save</button>
			</form>
<?php
$html->Close();
