<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';

class FewRights extends Page {
	public function __construct() {
		parent::__construct('few rights reserved');
	}

	protected static function MainContent(): void {
?>
		<h1>few rights reserved</h1>

		<p>
			the contents of track7 are copyright <em>few</em> rights reserved
			instead of <em>all</em> rights reserved. i don’t have much reason to
			impose limitations on how my work is used, so instead of reserving all
			rights i’m only reserving a few. note that some of the contents of
			track7 (mainly icons) come from other sources so it’s not up to me how
			other people can use them. most of what i’ve used that i didn’t create
			myself has a license that allows for my use, but some of it i couldn’t
			find license info.
		</p>

		<h2>linking / sharing</h2>
		<p>
			i put things on track7 because i want other people to have access to
			them. if you find something interesting or useful enough that you want
			to share it, please do! link to or share any page of track7 however you
			see fit. i do not provide quick-share links you can just click to
			quickly share a page from track7 on facebook or twitter or whatever, but
			that’s because i expect that my visitors know how to copy from the
			address bar and paste into the social media site of their choice and
			that i don’t pretend to be aware of every single such site out there.
			to share an image or download from track7 please link to or share my
			page that contains the image or file download link where possible.
		</p>
		<p>
			linking to my pages and not directly to my files is especially important
			for images. track7 doesn’t see a lot of traffic, which means it doesn’t
			cost me a lot to keep it running. if one of my images gets linked to
			directly from a page that does see a lot of traffic (for example, a
			popular forum), that means track7 needs to serve that image every time
			that page on the other site gets visited. enough of that (or one
			instance with a busy enough page) can cost me real money. that’s not to
			say i don’t want my images to appear on other sites, just if a lot of
			people are going to see it then it may help me out if you first download
			my image and put it up on an image hosting site.
		</p>

		<h2>credit</h2>
		<p>
			when using something from track7, don’t make it look like it’s your own
			work. sharing a link to a page on track7 makes it pretty clear it’s
			from track7, but linking directly to a download or posting an image do
			not. if at all possible add a link to either the page where you found
			it or to the track7 home page, with a statement that it comes from
			track7. if you used something you learned here to create something of
			your own then that’s something new and i’ll leave it up to you whether
			mentioning track7 makes sense.
		</p>

		<h2>i want to know</h2>
		<p>
			have you used something from track7? i’d love to see what you’ve done
			with it! this is by no means a requirement, but if you can share with
			me please do because i am definitely interested. mostly it’s that i’m
			curious, but i’m also happy to link to what you’ve done if i like it and
			you want me to. check <a href="/user/misterhaan/" title="view misterhaan’s profile">my profile</a>
			for ways to contact me.
		</p>

		<h2>stuff i didn’t make</h2>
		<p>
			some of the stuff on track7 was made by other people, so i don’t control
			the rights. the text font is <a href="https://fonts.google.com/specimen/Rosario">rosario</a>,
			header font is <a href="https://fonts.google.com/specimen/Monda">monda</a>,
			code font is <a href="https://github.com/microsoft/cascadia-code">cascadia code</a>,
			and icon font is <a href="http://fontawesome.io/">fontawesome</a>.
			markdown gets parsed into html by <a href="http://parsedown.org/">parsedown</a>
			in php. <a href="http://prismjs.com/">prism</a> puts syntax
			highlighting on code blocks (only using their javascript not their css).
			<a href="http://jquery.com/">jquery</a> makes my javascript work better
			and <a href="https://vuejs.org/">vue.js</a> helps my pages be more
			responsive. i’m also using <a href="http://www.jacklmoore.com/autosize/">jack moore’s autosize script</a>
			to keep multiline text boxes sized to their contents.
		</p>

<?php
	}
}
new FewRights();
