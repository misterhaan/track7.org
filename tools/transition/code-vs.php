<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('applications transition');
?>
			<h1>applications transition</h1>
			<p>sometimes, things need to change.</p>

			<h2>add .net 4.5</h2>
<?php
if($net45chk = $db->query('select id from code_vs_dotnet where version=\'4.5\' limit 1'))
	if($net45chk->fetch_object()) {
?>
			<p>.net 4.5 is already a choice.</p>
<?php
		AddStudio2019();
	} else {
		if($db->real_query('insert into code_vs_dotnet (version) values (\'4.5\')')) {
?>
			<p>.net 4.5 successfully added.</p>
<?php
			AddStudio2019();
		} else {
?>
			<p class=error>error adding .net 4.5:  <?=$db->errno; ?> <?=$db->error; ?></p>
<?php
		}
	}
else {
?>
			<p class=error>error checking if .net 4.5 has already been added:  <?=$db->errno; ?> <?=$db->error; ?></p>
<?php
}
$html->Close();

function AddStudio2019() {
	global $db;
?>

			<h2>add visual studio 2019</h2>
<?php
	if($vs2019chk = $db->query('select version from code_vs_studio where abbr=\'2019\' limit 1'))
		if($vs2019chk->fetch_object()) {
?>
			<p>visual studio 2019 is already a choice.</p>
<?php
		} else {
			if($db->real_query('insert into code_vs_studio (version, abbr, name) values (16.0, \'2019\', \'visual studio 2019\')')) {
?>
			<p>visual studio 2019 successfully added.</p>
<?php
			} else {
?>
			<p class=error>error adding visual studio 2019:  <?=$db->errno; ?> <?=$db->error; ?></p>
<?php
			}
		}
	else {
?>
			<p class=error>error checking if visual studio 2019 has already been added:  <?=$db->errno; ?> <?=$db->error; ?></p>
<?php
	}
}
