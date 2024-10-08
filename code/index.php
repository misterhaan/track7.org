<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class CodeIndex extends Page {
	public function __construct() {
		parent::__construct('software');
	}

	protected static function MainContent(): void {
?>
		<h1>software</h1>
		<p>
			i’ve been writing my own software for most of my life. some of it is
			here, and you can download it and use it yourself. this section of the
			site used to be called the analog underground, and that name appears as
			the company / copyright holder.
		</p>
		<nav id=codetypes>
			<section id=codevs>
				<h2><a href="vs/">applications</a></h2>
				<p>
					these applications are written in c# (or visual basic for the older
					ones) and are intended to be used on computers running windows.
					each application provides a windows installer package and has source
					code available for download.
				</p>
			</section>
			<section id=codescr>
				<h2><a href="web/">web scripts</a></h2>
				<p>
					anything i write for web servers or browsers is in this section.
					this includes applications and userscripts that can be installed
					without modification as well as code from track7 that can be adapted
					to your site.
				</p>
			</section>
			<section id=codeti>
				<h2><a href="calc/">calculator programs</a></h2>
				<p>
					i had a ti-85 and then a ti-86 graphing calculator in high school
					and college, and i wrote a few programs for them that did some of
					the stuff i was learning in my classes. haven’t had much use for a
					programmable calculator since college so don’t expect anything new
					here.
				</p>
			</section>
			<section id=codemzx>
				<h2><a href="games/">game worlds</a></h2>
				<p>
					i made a few game worlds for megazeux and started a couple more.
					the oldest one is actually for zzt. the most recent two have been
					unfinished since 1997 and i probably won’t get back to them.
				</p>
			</section>
		</nav>
<?php
	}
}
new CodeIndex();
