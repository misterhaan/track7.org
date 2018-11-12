<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

$html = new t7html([]);
$html->Open('post track7 update');
?>
			<h1>post track7 update</h1>
			<form id=editupdate method=post>
				<label class=multiline>
					<span class=label>update:</span>
					<span class=field><textarea id=markdown required rows="" cols=""></textarea></span>
				</label>
				<label>
					<span class=label>date:</span>
					<span class=field><input id="posted" placeholder="<?=t7format::LocalDate('Y-m-d g:i a', time()); ?>"></span>
				</label>
				<button id=save>save</button>
			</form>
<?php
$html->Close();
