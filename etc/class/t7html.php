<?php
define('_HTML_SITE_TITLE', 'track7');

class t7html {
	private $params;
	private $isopen = false;
	private $isclosed = false;

	public function t7html($params) {
		$this->params = $params;
	}

	public function Open($title) {
		if($this->isopen)
			return;
		$this->isopen = true;
		if(strpos($title, _HTML_SITE_TITLE) === false)
			$title .= ' - ' . _HTML_SITE_TITLE;
		header('X-Sven: look out for the fruits of life');
		header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang=en>
	<head>
		<meta charset=utf-8>
		<meta name=viewport content="width=device-width, initial-scale=1">
		<title><?php echo $title; ?></title>
		<link rel=stylesheet href="/track7.css">
		<script src="/jquery-3.3.1.min.js" type="text/javascript"></script>
		<script src="/autosize.min.js" type="text/javascript"></script>
<?php
		if(isset($this->params['ko']) && $this->params['ko']) {
?>
		<script src="/knockout-3.4.2.js" type="text/javascript"></script>
<?php
		}
		if(isset($this->params['vue']) && $this->params['vue'])
			if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/vue.js')) {
				?>
		<script src="/vue.js" type="text/javascript"></script>
<?php
			} else {
?>
		<script src="/vue.min.js" type="text/javascript"></script>
<?php
			}
?>
		<script src="/prism.js" type="text/javascript"></script>
		<script src="/track7.js" type="text/javascript"></script>
<?php
		if(substr($_SERVER['SCRIPT_NAME'], 0, 10) == '/user/via/') {
?>
		<script src="/user/via/register.js" type="text/javascript"></script>
<?php
		} elseif(file_exists(str_replace('.php', '.js', $_SERVER['SCRIPT_FILENAME']))) {
?>
		<script src="<?php echo str_replace('.php', '.js', $_SERVER['SCRIPT_NAME']); ?>" type="text/javascript"></script>
<?php
		}
?>
		<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
		<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
		<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
		<link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
		<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
		<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
		<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
		<link rel=icon type="image/png" sizes="192x192" href="/favicon-192x192.png">
		<link rel=icon type="image/png" sizes="96x96" href="/favicon-96x96.png">
		<link rel=icon type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel=icon type="image/png" sizes="32x32" href="/favicon-32x32.png">
<?php
		if(isset($this->params['rss'])) {
			$rss = $this->params['rss'];
			if(isset($rss['title']) && isset($rss['url'])) {
?>
		<link rel=alternate type=application/rss+xml title="<?php echo $rss['title']; ?>" href="<?php echo $rss['url']; ?>">
<?php
			}
		}
?>
		<meta name="msapplication-TileColor" content="#335577">
		<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	</head>
	<body class=<?php echo isset($this->params['bodytype']) ? $this->params['bodytype'] : 'text'; ?>>
		<header>
			<a id=gohome href="/" title="track7 home"><img src="/images/home.png" alt="track7"></a>
			<div id=userstatus>
<?php
		global $user;
		if($user->IsLoggedIn()) {
?>
				<a id=whodat href="/user/<?php echo $user->Username; ?>/"><?php echo htmlspecialchars($user->DisplayName); ?><?php if($user->NotifyCount) echo '<span class=notifycount>' . $user->NotifyCount . '</span>'; ?><img class=avatar src="<?php echo $user->Avatar; ?>" alt=""></a>
<?php
		} else {
?>
				<a id=signin href="#signin">sign in</a>
<?php
		}
?>
			</div>
		</header>
<?php
		if($user->IsLoggedIn()) {
?>
		<div id=usermenu>
			<nav id=useractions>
				<a class=profile href="/user/<?php echo $user->Username; ?>/">profile</a>
				<a class=settings href="/user/settings.php">settings<?php if($user->SettingsAlerts) echo '<span class=notifycount>' . $user->SettingsAlerts . '</span>'; ?></a>
				<a class=messages href="/user/messages.php">messages<?php if($user->UnreadMsgs) echo '<span class=notifycount>' . $user->UnreadMsgs . '</span>'; ?></a>
				<a id=logoutlink href="?logout">sign out</a>
			</nav>
		</div>
<?php
		} else {
?>
		<div id=loginmenu>
			<form id=signinform>
				sign in securely with your account from one of these sites:
				<div id=authchoices>
<?php
			$continue = isset($this->params['continue']) ? $this->params['continue'] : $_SERVER['REQUEST_URI'];
			foreach(t7auth::GetAuthLinks($continue) as $name => $authurl) {
?>
					<label class="<?=$name; ?>" title="sign in with your <?=$name; ?> account"><input type=radio name=login_url value="<?=htmlspecialchars($authurl); ?>"></label>
<?php
			}
?>
				</div>
				<div id=oldlogin>
					note:&nbsp; this is only for users who have already set up a password.
					<label>username: <input name=username maxlength=32></label>
					<label>password: <input name=password type=password></label>
				</div>
				<label for=rememberlogin><input type=checkbox id=rememberlogin name=remember> remember me</label>
				<button id=dologin disabled>choose site to sign in through</button>
			</form>
		</div>
<?php
		}
?>
		<main>
<?php

	}

	/**
	 * show a list of tags
	 * @param string $tagType type of tags to look up from tags api
	 * @param string $pluralName what to call multiple of the item that can be tagged
	 */
	public function ShowTags($tagType, $pluralName) {
?>
			<nav class="tagcloud hidden" data-tagtype=<?php echo $tagType; ?> v-if=tags.length>
				<header>tags</header>
				<template v-for="tag in tags">
				{{ }}
				<a :href="tag.name + '/'" :title="'<?php echo $pluralName; ?> tagged ' + tag.name" :data-count=tag.count>{{tag.name}}</a>
				</template>
			</nav>
<?php
	}

	/**
	 * show a 5-star voting apparatus for this thing.
	 * @param string $type prefix of the _votes table to use
	 * @param string $key typically the id of the thing being voted on
	 * @param integer $vote current vote in number of stars (1 through 5)
	 */
	public function ShowVote($type, $key, $vote) {
		echo '<span id=vote ';
		if($vote >= 1)
			echo 'class=voted ';
		echo 'data-type=' . $type . ' data-key=' . $key . ' data-vote=1 title="one star — bad"><span ';
		if($vote >= 2)
			echo 'class=voted ';
		echo 'data-vote=2 title="two stars — below average"><span ';
		if($vote >= 3)
			echo 'class=voted ';
		echo 'data-vote=3 title="three stars — average"><span ';
		if($vote >= 4)
			echo 'class=voted ';
		echo 'data-vote=4 title="four stars — above average"><span ';
		if($vote >= 5)
			echo 'class=voted ';
		echo 'data-vote=5 title="five stars — great"></span></span></span></span></span>';
	}

	/**
	 * show comments and form for adding comments.
	 * @param string $name display name of the type of thing the comments apply to
	 * @param string $type prefix of the _comments table to use
	 * @param string $key typically the id of the thing the comments apply to
	 */
	public function ShowComments($name, $type, $key) {
		global $user;
?>
			<section id=comments>
				<h2>comments</h2>
				<p v-if=error data-bind="visible: error(), text: error">{{error}}</p>
				<p v-if="!loading && !comments.length" data-bind="visible: !loadingComments() && comments().length == 0">
					there are no comments on this <?php echo $name; ?> so far.  you could be the first!
				</p>
				<!-- ko foreach: comments -->
				<section class=comment v-for="(comment, index) in comments">
					<div class=userinfo>
						<!-- ko if: username -->
						<template v-if=comment.username>
						<div class=username :class="{friend: comment.friend}" :title="comment.friend ? (comment.displayname || comment.username) + ' is your friend' : null" data-bind="css: {friend: friend}, attr: {title: friend ? (displayname || username) + ' is your friend' : null}">
							<a :href="'/user/' + comment.username + '/'" data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}">{{comment.displayname || comment.username}}</a>
						</div>
						<a :href="'/user/' + comment.username + '/'" data-bind="visible: avatar, attr: {href: '/user/' + username + '/'}"><img class=avatar alt="" :src=comment.avatar data-bind="attr: {src: avatar}"></a>
						<div class=userlevel data-bind="visible: level, text: level">{{comment.level}}</div>
						</template>
						<!-- /ko -->
						<!-- ko if: !username && contacturl -->
						<div class=username v-if="!comment.username && comment.contacturl"><a :href=comment.contacturl data-bind="text: name, attr: {href: contacturl}">{{comment.name}}</a></div>
						<!-- /ko -->
						<!-- ko if: !username && !contacturl -->
						<div v-if="!comment.username && !comment.contacturl" class=username data-bind="text: name">{{comment.name}}</div>
						<!-- /ko -->
					</div>
					<div class=comment>
						<header>posted <time :datetime=comment.posted.datetime data-bind="text: posted.display, attr: {datetime: posted.datetime}">{{comment.posted.display}}</time></header>
						<div v-if=!comment.editing class=content v-html=comment.html data-bind="visible: !editing(), html: html"></div>
						<div v-if=comment.editing class="content edit" data-bind="visible: editing">
							<textarea v-model=comment.markdown data-bind="value: markdown"></textarea>
						</div>
						<footer v-if=comment.canchange data-bind="visible: canchange">
							<a class="okay action" v-if=comment.editing v-on:click.prevent=Save(comment) data-bind="visible: editing(), click: $parent.SaveComment" href="/comments.php?ajax=save">save</a>
							<a class="cancel action" v-if=comment.editing v-on:click.prevent=Unedit(comment) data-bind="visible: editing(), click: $parent.UneditComment" href="#">cancel</a>
							<a class="edit action" v-if=!comment.editing v-on:click.prevent=Edit(comment) data-bind="visible: !editing(), click: $parent.EditComment" href="/comments.php?ajax=edit">edit</a>
							<a class="del action" v-if=!comment.editing v-on:click.prevent="Delete(comment, index)" data-bind="visible: !editing(), click: $parent.DeleteComment" href="/comments.php?ajax=delete">delete</a>
						</footer>
					</div>
				</section>

				<!-- /ko -->

				<form id=addcomment data-type=<?php echo $type; ?> data-key=<?php echo $key; ?>>
<?php
		if($user->IsLoggedIn()) {
?>
					<label title="you are signed in, so your comment will post with your avatar and a link to your profile">
						<span class=label>name:</span>
						<span class=field><a href="/user/<?php echo $user->Username; ?>/"><img class="inline avatar" src="<?php echo $user->Avatar; ?>"> <?php echo htmlspecialchars($user->DisplayName); ?></a></span>
					</label>
<?php
		} else {
?>
					<label title="please sign in or enter a name so we know what to call you">
						<span class=label>name:</span>
						<span class=field><input id=authorname></span>
					</label>
					<label title="enter a website, web page, or e-mail address if you want people to be able to find you">
						<span class=label>contact:</span>
						<span class=field><input id=authorcontact></span>
					</label>
<?php
		}
?>
					<label class=multiline title="enter your comments using markdown">
						<span class=label>comment:</span>
						<span class=field><textarea id=newcomment></textarea></span>
					</label>
					<button id=postcomment>post comment</button>
				</form>
			</section>
<?php
	}

	/**
	 * show a tag entry field that suggests existing tags.  requires the page to
	 * use vue and the vue model should include vueMixins.tagSuggest for the
	 * behavior.  read and write either tags (array) or taglist (comma-separated).
	 * also include newtags and deltags (both comma-separated) when saving so new
	 * tags can get added and old tags can get removed.
	 * @param string $type type of tags, used to look up suggestion list
	 */
	public function ShowTagsField($type) {
?>
				<label>
					<span class=label>tags:</span>
					<span class="field list" data-tagtype=<?php echo $type;?>>
						<span class=chosen v-for="(tag, index) in tags"><span>{{tag}}</span><a class="action del" href="#deltag" v-on:click.prevent=DelTag(index) :title="'remove the ' + tag + ' tag'"></a></span>
						<span class=suggestinput>
							<input id=tags autocomplete=off v-model=tagSearch v-on:keydown.down.prevent=NextTag v-on:keydown.up.prevent=PrevTag v-on:dblclick=ShowTagSuggestions v-on:blur=HideTagSuggestions(250) v-on:keydown.esc=HideTagSuggestions v-on:keydown.enter.prevent=AddCursorTag v-on:keydown.comma.prevent=AddTypedTag v-on:keydown.tab=AddCursorTag v-on:keydown.backspace=DelLastTag v-on:keydown=TagSearchKeyPress>
							<span class=suggestions v-if=showTagSuggestions>
								<span v-for="tag in tagChoices" v-html=tag :class="{selected: tag.replace(/<[^>]>/g, '') == tagCursor}" :title="'add the ' + tag + ' tag'" v-on:click=AddTag(tag)></span>
							</span>
						</span>
					</span>
				</label>
<?php
	}

	public function Close() {
		if(!$this->isopen || $this->isclosed)
			return;
		$this->isclosed = true;
?>
		</main>
		<footer>
			<a href="/feed.rss" title="add track7 activity to your feed reader">rss</a>
			<a href="https://twitter.com/track7feed" title="follow track7 on twitter">twitter</a>
			<a href="https://github.com/misterhaan/track7.org/blob/master<?php echo $_SERVER['SCRIPT_NAME']; ?>?ts=2" title="view the php source for this page on github">php source</a>
			<div id=copyright>© 1996 - 2018 track7 — <a href="/fewrights.php">few rights reserved</a></div>
		</footer>
	</body>
</html>
<?php
	}
}
