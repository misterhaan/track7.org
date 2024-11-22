<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'tag.php';

class TagsOverview extends Page {
	public function __construct() {
		parent::__construct('tags');
	}

	protected static function MainContent(): void {
?>
		<h1>tag information</h1>
		<div class=tabbed></div>
<?php
	}
}
new TagsOverview();
