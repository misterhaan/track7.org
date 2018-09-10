<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true]);
$html->Open('stories');
?>
			<h1>
				stories
				<a class=feed href="<?=dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of stories"></a>
			</h1>

			<p>
				one of the things i did to pass the time at school was write stories.
				it actually started in a junior high english class where the teacher was
				trying to show us that writing was fun, and i stuck with it until my
				college classes needed more of my focus.  some of the stories here i
				handed in as assignments, including some that are actually essays and
				not stories at all.  one of them is even a poem.  my more recent writing
				has been in the <a href="/bln/">blog</a> but i have a few ideas i might
				try to get out as stories.
			</p>

			<section id=storylist>
				<article v-for="story in stories" :class="{series: story.numstories}">
					<h2><a :href=story.url>{{story.title}}</a></h2>
					<p class=postmeta v-if="story.posted || story.numstories">
						<span v-if=story.numstories>a series of {{story.numstories}} stories</span>
						<span v-if=story.posted>posted <time :datetime=story.posted.datetime :title=story.posted.title>{{story.posted.display}}</time></span>
					</p>
					<div class=description v-html=story.deschtml></div>
				</article>
			</section>

<?php
$html->Close();
