<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$id = +$_GET['id'];
if($id)
	if($update = $db->query('select id, posted, html from update_messages where id=' . $id))
		if($update = $update->fetch_object()) {
			$update->posted = t7format::TimeTag('smart', $update->posted, t7format::DATE_LONG);
			$html = new t7html(['vue' => true]);
			$html->Open('track7 update');
?>
			<h1>track7 update</h1>
			<p class=guidemeta><time class=posted title="posted <?=$update->posted->title; ?>" datetime="<?=$update->posted->datetime; ?>"><?=$update->posted->display; ?></time></p>
<?php
			echo $update->html;
			$html->ShowComments('update', 'update', $update->id);
			$html->Close();
		} else {
			header('HTTP/1.0 404 Not Found');
			include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
			die;
		}
	else {
		$html = new t7html([]);
		$html->Open('track7 update');
?>
			<h1>track7 update</h1>
			<p class=error>error looking up update:  <?=$db->error; ?></p>
<?php
		$html->Close();
	}
else
	header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
