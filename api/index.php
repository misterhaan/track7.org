<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('api');
?>
			<h1>track7 api</h1>

			<h2 class=api><a href=activity>activity</a></h2>
			<p>the activity api retrieves latest activity.</p>

			<h2 class=api><a href=applications>applications</a></h2>
			<p>the applications api manages applications with source code.</p>

			<h2 class=api><a href=art>art</a></h2>
			<p>the art api manages art.</p>

			<h2 class=api><a href=blog>blog</a></h2>
			<p>the blog api manages blog entries.</p>

			<h2 class=api><a href=comments>comments</a></h2>
			<p>the comments api manages comments.</p>

			<h2 class=api><a href=guides>guides</a></h2>
			<p>the guides api manages guides.</p>

			<h2 class=api><a href=legos>legos</a></h2>
			<p>the photos api manages lego models.</p>

			<h2 class=api><a href=photos>photos</a></h2>
			<p>the photos api manages the photo album.</p>

			<h2 class=api><a href=stories>stories</a></h2>
			<p>the stories api manages stories and other writings.</p>

			<h2 class=api><a href=tags>tags</a></h2>
			<p>the tags api manages tags for everything that uses tags.</p>

			<h2 class=api><a href=validate>validate</a></h2>
			<p>the validate api provides field-level validation for forms.</p>
<?php
$html->Close();
