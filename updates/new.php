<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	if(isset($_GET['ajax'])) {
		$ajax = new t7ajax();
		$ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
		$ajax->Send();
		die;
	}
	header('HTTP/1.0 404 Not Found');
	include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'save': SaveUpdate(); break;
	}
	$ajax->Send();
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
					<span class=field><input id="posted" placeholder="<?php echo t7format::LocalDate('Y-m-d g:i a', time()); ?>"></span>
				</label>
				<button id=save>save</button>
			</form>
<?php
$html->Close();

function SaveUpdate() {
	global $ajax, $db;
	$html = trim($_POST['markdown']);
	if($html) {
		$html = t7format::Markdown($html);
		$posted = trim($_POST['posted']);
		$posted = $posted ? t7format::LocalStrtotime($posted) : time();
		if($save = $db->prepare('insert into update_messages (posted, html) values (?, ?)')) {
			if($save->bind_param('is', $posted, $html))
				if($save->execute()) {
					$ajax->Data->id = $save->insert_id;
					t7send::Tweet('track7 update', t7format::FullUrl('/updates/' . $ajax->Data->id));
				} else
					$ajax->Fail('error saving update:  ' . $save->error);
			else
				$ajax->Fail('error binding parameters to save update:  ' . $save->error);
			$save->close();
		} else
			$ajax->Fail('error preparing to save update:  ' . $db->error);
	} else
		$ajax->Fail('update is required.');
}
