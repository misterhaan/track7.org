<?php
define('MAX_COMMENT_GET', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$u = FindUser();

$html = new t7html(['vue' => true]);
if($u) {
	$html->Open(htmlspecialchars($u->displayname) . '’s comments');
?>
			<h1 data-user=<?=$u->id; ?>>
				<a href="/user/<?=$u->username; ?>/">
					<img class="inline avatar" src="<?=$u->avatar; ?>">
					<?=htmlspecialchars($u->displayname); ?></a>’s comments
			</h1>
<?php
} else {
	$html->Open('comments');
?>
			<h1 data-user=all>
				comments
				<a class=feed href="<?=str_replace('.php', '.rss', $_SERVER['PHP_SELF']); ?>" title="rss feed of comments"></a>
			</h1>
<?php
}
?>
			<div id=comments>
				<p class=error v-if="error">{{error}}</p>
				<p class=info v-if="!loading && !error && comments.length == 0">no comments found</p>
				<template v-for="comment in comments">
				<h3><a :href=comment.url>{{comment.title}}</a></h3>
				<section class=comment>
					<div class=userinfo>
						<div class=username v-if=comment.username :class="{friend: comment.friend}" :title="comment.friend ? (comment.displayname || comment.username) + ' is your friend' : null">
							<a :href="'/user/' + comment.username + '/'">{{comment.displayname || comment.username}}</a>
						</div>
						<a v-if="comment.username && comment.avatar" :href="'/user/' + comment.username + '/'"><img class=avatar alt="" :src=comment.avatar></a>
						<div class=userlevel v-if="comment.username && comment.level">{{comment.level}}</div>
						<div class=username v-if="!comment.username && comment.contacturl"><a :href=comment.contacturl>{{comment.name}}</a></div>
						<div class=username v-if="!comment.username && !comment.contacturl">{{comment.name}}</div>
					</div>
					<div class=comment>
						<header>posted <time :datetime=comment.posted.datetime>{{comment.posted.display}}</time></header>
						<div class=content v-if=!comment.editing v-html=comment.html></div>
						<div class="content edit" v-if=comment.editing>
							<textarea v-model=comment.markdown></textarea>
						</div>
						<footer v-if=comment.canchange>
							<a class="okay action" v-if=comment.editing v-on:click.prevent=Save(comment) href="/api/comments/save">save</a>
							<a class="cancel action" v-if=comment.editing v-on:click.prevent=Unedit(comment) href="#cancelEdit">cancel</a>
							<a class="edit action" v-if=!comment.editing v-on:click.prevent=Edit(comment) href="#edit">edit</a>
							<a class="del action" v-if=!comment.editing v-on:click.prevent=Delete(comment) href="/api/comments/delete">delete</a>
						</footer>
					</div>
				</section>

				</template>
				<p class=loading v-if=loading>loading comments . . .</p>
				<p class=calltoaction v-if=hasMore v-on:click.prevent=Load><a class="get action" href="/api/comments/keyed">load more comments</a></p>
			</div>
<?php
$html->Close();

/**
 * Find the user whose comments are being displayed (if not all users).  Will
 * redirect to the list of users if unable to find the user.
 * @param string $_GET['username'] username to display comments from (unset for all users)
 * @return object user object with id, username, displayname, and avatar
 */
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
