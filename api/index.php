<?php
require_once dirname(__DIR__) . '/etc/class/page.php';

class ApiIndexPage extends Page {
	public function __construct() {
		parent::__construct('api');
	}

	protected static function MainContent(): void {
?>
		<h1>track7 api</h1>

		<h2 class=api><a href=activity.php>activity</a></h2>
		<p>the activity api retrieves latest activity.</p>

		<h2 class=api><a href=application.php>application</a></h2>
		<p>the applications api manages applications with source code.</p>

		<h2 class=api><a href=art.php>art</a></h2>
		<p>the art api manages art.</p>

		<h2 class=api><a href=blog.php>blog</a></h2>
		<p>the blog api manages blog entries.</p>

		<h2 class=api><a href=comment.php>comment</a></h2>
		<p>the comment api manages comments.</p>

		<h2 class=api><a href=contact.php>contact</a></h2>
		<p>the contact api manages contact links.</p>

		<h2 class=api><a href=date.php>date</a></h2>
		<p>the date api manages dates.</p>

		<h2 class=api><a href=forum.php>forum</a></h2>
		<p>the forum api manages the forums.</p>

		<h2 class=api><a href=guide.php>guide</a></h2>
		<p>the guide api manages guides.</p>

		<h2 class=api><a href=lego.php>lego</a></h2>
		<p>the lego api manages lego models.</p>

		<h2 class=api><a href=message.php>message</a></h2>
		<p>the message api manages messages to track7 users.</p>

		<h2 class=api><a href=photo.php>photo</a></h2>
		<p>the photo api manages the photo album.</p>

		<h2 class=api><a href=release.php>release</a></h2>
		<p>the release api manages application releases.</p>

		<h2 class=api><a href=settings.php>settings</a></h2>
		<p>the settings api manages user profiles and settings.</p>

		<h2 class=api><a href=story.php>story</a></h2>
		<p>the story api manages stories and other writings.</p>

		<h2 class=api><a href=tag.php>tag</a></h2>
		<p>the tag api manages tags for everything that uses tags.</p>

		<h2 class=api><a href=tool.php>tool</a></h2>
		<p>the tool api performs actions for managing track7.</p>

		<h2 class=api><a href=update.php>update</a></h2>
		<p>the update api manages site updates.</p>

		<h2 class=api><a href=user.php>user</a></h2>
		<p>the user api manages users.</p>

		<h2 class=api><a href=vote.php>vote</a></h2>
		<p>the vote api manages votes.</p>

		<h2 class=api><a href=web.php>web</a></h2>
		<p>the web api manages web script code.</p>

<?php
	}
}
new ApiIndexPage();
