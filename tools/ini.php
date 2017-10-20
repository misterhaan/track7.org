<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$access = [0, 'user', 'directory', 3, 'system', 5, 'directory or system', 'all'];

$html = new t7html([]);
$html->Open('php.ini');

$ini = ini_get_all();
foreach($ini as $key => $values) {
?>
			<h2><?=$key; ?></h2>
			<p>
				<strong>global:</strong>&nbsp; <?=htmlspecialchars($values['global_value']); ?><br />
				<strong>local:</strong>&nbsp; <?=htmlspecialchars($values['local_value']); ?><br />
				<strong>access:</strong>&nbsp; <?=$access[$values['access']]; ?>
			</p>

<?php
}
$html->Close();
