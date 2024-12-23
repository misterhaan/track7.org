<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class ServerData extends Page {
	public function __construct() {
		parent::__construct('$_SERVER');
	}

	protected static function MainContent(): void {
?>
		<h1>$_SERVER</h1>
		<?php
		foreach ($_SERVER as $tag => $data) {
		?>
			<h2><?= $tag; ?></h2>
			<pre><code><?= $data; ?></code></pre>

<?php
		}
	}
}
new ServerData();
