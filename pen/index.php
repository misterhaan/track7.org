<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class StoryIndex extends Page {
	public function __construct() {
		parent::__construct('stories');
	}

	protected static function MainContent(): void {
?>
		<h1>stories</h1>

		<p>
			one of the things i did to pass the time at school was write stories.
			it actually started in a junior high english class where the teacher was
			trying to show us that writing was fun, and i stuck with it until my
			college classes needed more of my focus. some of the stories here i
			handed in as assignments, including some that are actually essays and
			not stories at all. one of them is even a poem. my more recent writing
			has been in the <a href="/bln/">blog</a> but i have a few ideas i might
			try to get out as stories.
		</p>

		<section id=storylist></section>
<?php
	}
}
new StoryIndex();
