<?php
define('MAX_THREADS', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$u = FindUser();

$html = new t7html(['vue' => true]);
if($u) {
	$html->Open(htmlspecialchars($u->displayname) . '’s latest replies');
?>
			<h1 data-user=<?=$u->id; ?>>
				<a href="/user/<?=$u->username; ?>/">
					<img class="inline avatar" src="<?=$u->avatar; ?>">
					<?=htmlspecialchars($u->displayname); ?></a>’s latest replies
			</h1>
<?php
} else {
	$html->Open('latest replies');
?>
			<h1>
				latest replies
				<a class=feed href="<?=dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of the forum"></a>
			</h1>
<?php
}
?>
			<p class=info v-if="!loading && !replies.length">no forum activity</p>

			<!-- ko foreach: replies -->
			<template v-for="reply in replies">
			<h2><a :href="'<?=dirname($_SERVER['PHP_SELF']); ?>/' + reply.discussion + '#r' + reply.id">{{reply.title}}</a></h2>
			<section class=comment :id="'r' + reply.id">
				<div class=userinfo>
					<!-- ko if: username -->
					<template v-if=reply.username>
					<div class=username :class="{friend: reply.friend}" :title="reply.friend ? (reply.displayname || reply.username) + ' is your friend' : null">
						<a :href="'/user/' + reply.username + '/'">{{reply.displayname || reply.username}}</a>
					</div>
					<a v-if=reply.avatar :href="'/user/' + reply.username + '/'" ><img class=avatar alt="" :src=reply.avatar></a>
					<div class=userlevel v-if=reply.level>{{reply.level}}</div>
					</template>
					<!-- /ko -->
					<!-- ko if: !username && contacturl -->
					<div class=username v-if="!reply.username && reply.contacturl"><a :href=reply.contacturl>{{reply.name}}</a></div>
					<!-- /ko -->
					<!-- ko if: !username && !contacturl -->
					<div class=username v-if="!reply.username && !reply.contacturl">{{reply.name}}</div>
					<!-- /ko -->
				</div>
				<div class=comment>
					<header>posted <time :datetime=reply.posted.datetime>{{reply.posted.display}}</time></header>
					<div class=content v-if=!reply.editing v-html=reply.html></div>
					<div class="content edit" v-if=reply.editing><textarea v-model=reply.markdown></textarea></div>
					<div class=meta>
						<div v-for="edit in reply.edits" class=edithistory>
							edited
							<time :datetime=edit.datetime>{{edit.posted}}</time>
							by
							<a :href="'/user/' + edit.username + '/'">{{edit.displayname || edit.username}}</a>
						</div>
					</div>
					<footer v-if=reply.canchange>
						<!-- ko if: editing() -->
						<a class="okay action" v-if=reply.editing v-on:click.prevent=Save(reply) href="/api/forum/update">save</a>
<?php
if($user->IsTrusted()) {
?>
						<a class="okay action" v-if=reply.editing v-on:click.prevent="Save(reply, true)" href="/api/forum/update">stealth save</a>
<?php
}
?>
						<a class="cancel action" v-if=reply.editing v-on:click.prevent=Unedit(reply) href="#cancel">cancel</a>
						<!-- /ko -->
						<!-- ko ifnot: editing() -->
						<a class="edit action" v-if=!reply.editing v-on:click.prevent=Edit(reply) href="#edit">edit</a>
						<a class="del action" v-if=!reply.editing v-on:click.prevent=Delete(reply) href="/api/forum/delete">delete</a>
						<!-- /ko -->
					</footer>
				</div>
			</section>

			</template>
			<!-- /ko -->

			<p class=loading v-if=loading>loading replies . . .</p>

			<p class=calltoaction v-if="more && !loading"><a class="action get" v-on:click.prevent=Load href="/api/forum/replies">load more replies</a></p>
<?php
$html->Close();

function FindUser() {
	global $db;
	if(isset($_GET['username'])) {
		if($u = $db->prepare('select id, username, displayname, avatar from users where username=? limit 1'))
			if($u->bind_param('s', $_GET['username']))
				if($u->execute())
					if($u->bind_result($id, $username, $displayname, $avatar))
						if($u->fetch())
							return (object)['id' => $id, 'username' => $username, 'displayname' => $displayname ? $displayname : $username, 'avatar' => $avatar ? $avatar : t7user::DEFAULT_AVATAR];
		if(substr($_SERVER['REQUEST_URI'], 0, 6) == '/user/') {
			header('Location: ' . t7format::FullUrl($_SERVER['PHP_SELF']));
			die;
		}
	}
	return false;
}
