<?php
define('MAX_UPDATE_GET', 16);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true, 'rss' => ['title' => 'updates', 'url' => 'feed.rss']]);
$html->Open('track7 updates');
?>
			<h1>
				track7 updates
				<a class="feed" href="feed.rss" title="rss feed of track7 updates"></a>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<div class=floatbgstop><nav class=actions><a class=new href="new.php">add update message</a></nav></div>
<?php
}
?>
			<!-- ko foreach: updates -->
			<article class="activity update" v-for="update in updates">
				<div class=whatwhen :title="'site update at ' + update.posted.title">
					<time :datetime=update.posted.datetime v-html=update.posted.display></time>
				</div>
				<div>
					<h2></h2>
					<div class=summary v-html=update.html></div>
					<p><a :href=update.id>{{update.comments == 1 ? '1 comment' : update.comments + ' comments'}}</a></p>
				</div>
			</article>
			<!-- /ko -->
			<nav class="showmore calltoaction" v-if="hasmore && !loading"><a class="action get" href="#loadmore" v-on:click=Load>load older updates</a></nav>
			<p class=loading v-if=loading>loading . . .</p>
<?php
$html->Close();
