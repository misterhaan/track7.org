<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class PhpIni extends Page {
	public function __construct() {
		parent::__construct('php.ini');
	}

	protected static function MainContent(): void {
		$access = [0, 'user', 'directory', 3, 'system', 5, 'directory or system', 'all'];
		$ini = ini_get_all();
		foreach ($ini as $key => $values) {
?>
			<h2><?= $key; ?></h2>
			<p>
				<strong>global:</strong> <?= htmlspecialchars($values['global_value']); ?><br />
				<strong>local:</strong> <?= htmlspecialchars($values['local_value']); ?><br />
				<strong>access:</strong> <?= $access[$values['access']]; ?>
			</p>

<?php
		}
	}
}
new PhpIni();
