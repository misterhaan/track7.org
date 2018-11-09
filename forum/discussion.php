<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$discussion = false;
if(isset($_GET['id']) && $discussion = $db->query('select d.id, d.title, group_concat(t.name order by t.name) as tags from forum_discussions as d left join forum_discussion_tags as dt on dt.discussion=d.id left join forum_tags as t on t.id=dt.tag where d.id=\'' . +$_GET['id'] . '\' group by d.id limit 1'))
	$discussion = $discussion->fetch_object();
if(!$discussion) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('discussion not found - forum');
?>
			<h1>404 discussion not found</h1>

			<p>
				sorry, we don’t seem to have a discussion with that id.  try the list of
				<a href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">all discussions</a>.
			</p>
<?php
	$html->Close();
	die;
}
$discussion->tags = explode(',', $discussion->tags);

$html = new t7html(['vue' => true]);
$html->Open(htmlspecialchars($discussion->title) . ' - forum');
$taglinks = [];
foreach($discussion->tags as $tag)
	$taglinks[] = '<a href="' . dirname($_SERVER['SCRIPT_NAME']) . '/' . rawurlencode($tag) . '/">' . htmlspecialchars($tag) . '</a>';
?>
			<h1 data-discussion=<?=+$discussion->id; ?>><?=htmlspecialchars($discussion->title); ?></h1>
			<p class=meta><span class=tags><?=implode(', ', $taglinks); ?></span></p>

			<div id=discussion>
				<section class=comment v-for="reply in replies" :id="'r' + reply.id">
					<div class=userinfo>
						<template v-if=reply.id>
						<div class=username :class="{friend: reply.friend}" :title="reply.friend ? (reply.displayname || reply.username) + ' is your friend' : null">
							<a :href="'/user/' + reply.username + '/'">{{reply.displayname || reply.username}}</a>
						</div>
						<a v-if=reply.avatar :href="'/user/' + reply.username + '/'"><img class=avatar alt="" :src=reply.avatar></a>
						<div class=userlevel v-if=reply.level>{{reply.level}}</div>
						</template>
						<div v-if="!reply.username && reply.contacturl" class=username><a :href=reply.contacturl>{{reply.name}}</a></div>
						<div v-if="!reply.username && !reply.contacturl" class=username>{{reply.name}}</div>
					</div>
					<div class=comment>
						<header>posted <time :datetime=reply.posted.datetime>{{reply.posted.display}}</time></header>
						<div class=content v-if=!reply.editing v-html=reply.html></div>
						<div class="content edit" v-if=reply.editing><textarea v-model=reply.markdown></textarea></div>
						<div class=meta>
							<div class=edithistory v-for="edit in reply.edits">
								edited
								<time :datetime=edit.datetime>{{edit.posted}}</time>
								by
								<a :href="'/user/' + edit.username + '/'">{{edit.displayname || edit.username}}</a>
							</div>
						</div>
						<footer v-if=reply.canchange>
							<template v-if=reply.editing>
							<a class="okay action" href="/api/forum/reply" v-on:click.prevent=SaveReply(reply)>save</a>
<?php
if($user->IsTrusted()) {
?>
							<a class="okay action" href="/api/forum/reply" v-on:click.prevent="SaveReply(reply, true)">stealth save</a>
<?php
}
?>
							<a class="cancel action" href=#cancel v-on:click.prevent=UneditReply(reply)>cancel</a>
							</template>
							<template v-if=!reply.editing>
							<a class="edit action" href=#edit v-on:click.prevent=EditReply(reply)>edit</a>
							<a class="del action" href="/api/forum/delete" v-on:click.prevent=DeleteReply(reply)>delete</a>
							</template>
						</footer>
					</div>
				</section>

				<h2>add a reply</h2>
				<form id=addreply v-on:submit.prevent=AddReply>
<?php
		if($user->IsLoggedIn()) {
?>
					<label title="you are signed in, so your reply will post with your avatar and a link to your profile">
						<span class=label>name:</span>
						<span class=field><a href="/user/<?=$user->Username; ?>/"><?=htmlspecialchars($user->DisplayName); ?></a></span>
					</label>
<?php
		} else {
?>
					<label title="please sign in or enter a name so we know what to call you">
						<span class=label>name:</span>
						<span class=field><input name=authorname maxlength=48></span>
					</label>
					<label title="enter a website, web page, or e-mail address if you want people to be able to find you">
						<span class=label>contact:</span>
						<span class=field><input name=authorcontact maxlength=255></span>
					</label>
<?php
		}
?>
					<label class=multiline title="enter your reply using markdown">
						<span class=label>reply:</span>
						<span class=field><textarea name=markdown></textarea></span>
					</label>
					<button :disabled=saving :class="{working: saving}">post reply</button>
				</form>
			</div>
<?php
$html->Close();
