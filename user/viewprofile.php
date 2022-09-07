<?php
define('MAXACTIONS', 12);

if(isset($_GET['login'])) {
	require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
	$u = new t7user($_GET['login']);
	if(!$u->IsLoggedIn())
		$u = new oldUser($_GET['login']);
	if($u->IsLoggedIn()) {
		$u->DisplayName = htmlspecialchars($u->DisplayName);
		$stats = $u->GetStats();
		$html = new t7html(['vue' => true]);
		$html->Open($u->DisplayName);
?>
			<header class=profile>
				<img class=avatar src="<?=htmlspecialchars($u->Avatar); ?>" alt="">
				<div>
					<h1 data-userid="<?=$u->ID; ?>" <?php if($u->Fan) echo ' class=friend title="' . $u->DisplayName . ' is your friend"'; ?>><?=$u->DisplayName; ?></h1>
					<p><?=$u->GetLevelName(); ?>, joined <time datetime="<?=gmdate('c', $stats->registered); ?>" title="<?=t7format::LocalDate(t7format::DATE_LONG, $stats->registered); ?>"><?=t7format::HowLongAgo($stats->registered); ?> ago</time></p>
				</div>
			</header>
<?php
		if(!is_a($u, 'oldUser')) {
?>
			<nav class=actions>
<?php
			if($u->ID != $user->ID) {
?>
				<a class=message title="send <?=$u->DisplayName; ?> a private message" href="/user/messages.php#!to=<?=htmlspecialchars($u->Username); ?>">send message</a>
<?php
			}
			if($user->IsLoggedIn())
				if($u->ID == $user->ID) {
?>
				<a class=edit title="edit your profile" href="/user/settings.php">edit profile</a>
<?php
				} elseif($u->Fan) {
?>
				<a class=removefriend title="remove <?=$u->DisplayName; ?> from your friends" href="/api/users/removeFriend?friend=<?=$u->ID; ?>">remove friend</a>
<?php
				} else {
?>
				<a class=addfriend title="add <?=$u->DisplayName; ?> as a friend" href="/api/users/addFriend?friend=<?=$u->ID; ?>">add friend</a>
<?php
				}
?>
			</nav>
<?php
		}
		if(count($links = $u->GetContactLinks())) {
?>
			<section id=contact>
<?php
			foreach($links as $link) {
?>
				<a class=<?=$link['type']; ?> href="<?=htmlspecialchars($link['url']); ?>" title="<?=$link['title']; ?>"></a>
<?php
			}
?>
			</section>
<?php
		}
		if($stats->fans || $stats->comments || $stats->replies) {
?>
			<section id=rank>
				<header>rankings</header>
				<ul>
<?php
			if($stats->fans) {
?>
					<li>#<?=Rank('fans', $stats->fans); ?> in fans with <?=$stats->fans; ?></li>
<?php
			}
			if($stats->comments) {
?>
					<li>#<?=Rank('comments', $stats->comments); ?> in <a href="<?=dirname($_SERVER['PHP_SELF']); ?>/<?=$u->Username; ?>/comments" title="view all of <?=$u->Username; ?>’s comments">comments</a> with <?=$stats->comments; ?></li>
<?php
			}
			if($stats->replies) {
?>
					<li>#<?=Rank('replies', $stats->replies); ?> in <a href="<?=dirname($_SERVER['PHP_SELF']); ?>/<?=$u->Username; ?>/replies" title="view all of <?=$u->Username; ?>’s forum posts">forum posts</a> with <?=$stats->replies; ?></li>
<?php
			}
?>
				</ul>
			</section>
<?php
		}
?>
			<section id=activity>
				<p v-if="!loading && activity.length < 1"><?=$u->DisplayName; ?> hasn’t posted anything to track7 yet.</p>
				<ol data-bind="foreach: activity">
					<li v-for="act in activity" :class=act.conttype>
						<span class=action data-bind="text: action">{{act.action}}</span>
						<a :href=act.url>{{act.title}}</a>
						<time :datetime=act.posted.datetime :title=act.posted.title>{{act.posted.display}}</time>
						ago
					</li>
				</ol>
				<p class=loading v-if=loading>loading more activity...</p>
				<p class="more calltoaction" v-if="!loading && more"><a href=#activity class="action get" v-on:click=Load>show more activity from <?=$u->DisplayName; ?></a></p>
			</section>
<?php
		$html->Close();
	} else  // user not found; go to user index
		header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
} else  // user not specified; go to user index
	header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));

/**
 * Count how many users have at least the specified value in a certain stat.
 * @param string $stat Field name of the stat to check
 * @param integer $value User's value for the stat
 * @return integer|string value to display as the rank
 */
function Rank($stat, $value) {
	global $db;
	switch($stat) {
		case 'fans':
		case 'comments':
		case 'replies':
			if($r = $db->query('select count(1) as `rank` from users_stats where ' . $stat . '>=' . +$value))
				if($r = $r->fetch_object())
					return $r->rank;
			break;
	}
	return '<em title=unknown>?</em>';
}

/**
 * Version of the profile page portion of t7user that uses the old database
 * instead.
 * @author misterhaan
 */
class oldUser {
	private $found = false;
	public $ID = false;
	public $Username = false;
	public $DisplayName = false;
	public $Avatar = false;
	private $Friend = false;
	public $Fan = false;
	private $olddb = false;

	public function oldUser($login) {
		global $user;
		require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/.dbinfo.track7.php';
		$this->olddb = new mysqli(_DB_HOST, _DB_USER, _DB_PASS, _DB_NAME);
		$this->olddb->real_query('set names \'utf8\'');
		$this->olddb->set_charset('utf8');
		if($u = $this->olddb->query('select u.uid, u.login, p.avatar, fa.fanuid, fr.frienduid from users as u left join userprofiles as p on p.uid=u.uid left join userfriends as fr on fr.fanuid=u.uid and fr.frienduid=\'' . $user->OldID() . '\' left join userfriends as fa on fa.frienduid=u.uid and fa.fanuid=\'' . $user->OldID() . '\' where login=\'' . $this->olddb->escape_string($login) . '\''))
			if($u = $u->fetch_object()) {
				$this->found = true;
				$this->ID = $u->uid;
				$this->Username = $u->login;
				$this->DisplayName = $u->login;
				$this->Avatar = $u->avatar ? '/user/avatar/' . $u->login . '.' . $u->avatar : '/images/user.jpg';
				$this->Friend = $u->frienduid;
				$this->Fan = $u->fanuid;
			}
	}

	public function IsLoggedIn() {
		return $this->found;
	}

	public function GetStats() {
		if($s = $this->olddb->query('select since as registered, fans, comments from userstats where uid=\'' . +$this->ID . '\''))
			if($s = $s->fetch_object()) {
				$s->replies = 0;
				return $s;
			}
		return false;
	}

	public function GetLevelName() {
		return 'old';
	}

	public function GetContactLinks() {
		global $user;
		$links = [];
		if($c = $this->olddb->query('select email, website, twitter, steam, flags from usercontact where uid=\'' . +$this->ID . '\' limit 1'))
			if($c = $c->fetch_object()) {
				if($c->email && ($c->flags & 1 || $user->IsAdmin()))
					$links[] = ['type' => 'email', 'url' => 'mailto:' . $c->email, 'title' => 'send ' . $this->DisplayName . ' an e-mail'];
				if($c->website)
					$links[] = ['type' => 'www', 'url' => $c->website, 'title' => 'visit ' . $this->DisplayName . '’s website'];
				if($c->twitter)
					$links[] = ['type' => 'twitter', 'url' => self::ExpandProfileLink($c->twitter, 'twitter'), 'title' => 'view ' . $this->DisplayName . '’s twitter profile'];
				if($c->steam)
					$links[] = ['type' => 'steam', 'url' => self::ExpandProfileLink($c->steam, 'steam'), 'title' => 'view ' . $this->DisplayName . '’s steam community profile'];
			}
		return $links;
	}

	public function OldID() {
		return $this->ID;
	}
}
