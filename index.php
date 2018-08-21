<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true, 'rss' => ['title' => 'unifeed', 'url' => '/feed.rss']]);
$html->Open('track7');
?>
			<h1><img alt=track7 src="/images/track7.png"></h1>

			<section id=features>
				<nav>
					<a v-for="feat in features" :href="'/' + feat.id + '/'" :title=desc>
						<img :src="'/' + feat.id + '/favicon.png'" alt="">
						{{feat.name}}
					</a>
<?php
if($user->IsAdmin()) {
?>
					<a href="/tools/" title="administer track7">
						<img src="/favicon.png" alt="">
						tools
					</a>
<?php
}
?>
				</nav>
			</section>
<?php
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions><a class=new href="/updates/new.php">add update message</a></nav></div>
<?php
}
?>
			<div id=latestactivity>
				<article class="activity" v-for="act in activity" :class=act.conttype>
					<div class=whatwhen :title="act.conttype + ' at ' + act.posted.title">
						<time v-html=act.posted.display :datetime=act.posted.datetime></time>
					</div>
					<div>
						<h2>
							<span>{{act.prefix}}</span>
							<a :href=act.url>{{act.title}}</a>
							<span>{{act.postfix}}</span>
							by
							<a v-if=act.username :href="'/user/' + act.username + '/'" :title="'view ' + (act.displayname || act.username) + '’s profile'">{{act.displayname || act.username}}</a>
							<a v-if="!act.username && act.authorurl" :href=act.authorurl>{{act.authorname}}</a>
							<span v-if="!act.username && !act.authorurl">{{act.authorname}}</span>
						</h2>
						<div class=summary v-html=act.preview></div>
						<p v-if=act.hasmore class=readmore><a class=continue :href=act.url>read more</a></p>
					</div>
				</article>
				<p class=loading v-if=loading>loading activity...</p>
				<p class="more calltoaction" v-if="more && !loading"><a class="action get" href="/api/activity/latest" v-on:click.prevent=Load>show more activity</a></p>
			</div>
<?php
$html->Close();
