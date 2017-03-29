<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('$_SERVER')
?>
			<h1>$_SERVER</h1>
<?php
foreach($_SERVER as $tag => $data) {
?>
			<h2><?php echo $tag; ?></h2>
			<pre><code><?php echo $data; ?></code></pre>

<?php
}
$html->Close();
