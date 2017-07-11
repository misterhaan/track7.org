<?php
define('MAXACTIONS', 12);

if(isset($_GET['login'])) {
	require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
	$u = new t7user($_GET['login']);
	if(!$u->IsLoggedIn())
		$u = new oldUser($_GET['login']);
	if(isset($_GET['ajax'])) {
		$ajax = new t7ajax();
		if($u->IsLoggedIn())
			switch($_GET['ajax']) {
				case 'activity':
					$before = isset($_GET['before']) && +$_GET['before'] ? +$_GET['before'] : false;
					if($acts = t7contrib::GetUser($u->ID, $before, MAXACTIONS)) {
						$ajax->Data->acts = [];
						$ajax->Data->latest = false;
						while($act = $acts->fetch_object()) {
							$ajax->Data->latest = $act->posted;
							$act->action = t7contrib::ActionWords($act->conttype);
							$act->posted = t7format::TimeTag('ago', $act->posted, 'g:i a \o\n l F jS Y');
							$ajax->Data->acts[] = $act;
						}
						$ajax->Data->more = t7contrib::More($ajax->Data->latest, $u->ID);
					} else
						$ajax->Fail('error looking up activity:  ' . $db->error);
					break;
			}
		else
			$ajax->Fail('user not found');
		$ajax->Send();
		die;
	}
	if($u->IsLoggedIn()) {
		$u->DisplayName = htmlspecialchars($u->DisplayName);
		$stats = $u->GetStats();
		$html = new t7html(['ko' => true]);
		$html->Open($u->DisplayName);
?>
			<header class=profile>
				<img class=avatar src="<?php echo htmlspecialchars($u->Avatar); ?>" alt="">
				<div>
					<h1>
						<?php echo $u->DisplayName; ?>
<?php
		if($u->Fan) {
?>
						<img class=friend src="/images/friend.png" alt="☆" title="<?php echo $u->DisplayName; ?> is your friend">
<?php
		}
?>
					</h1>
					<p><?php echo $u->GetLevelName(); ?>, joined <time datetime="<?php echo gmdate('c', $stats->registered); ?>" title="<?php echo t7format::LocalDate('g:i a \o\n l F jS Y', $stats->registered); ?>"><?php echo t7format::HowLongAgo($stats->registered); ?> ago</time></p>
				</div>
			</header>
<?php
		if(!is_a($u, 'oldUser')) {
?>
			<nav class=actions>
<?php
			if($u->ID != $user->ID) {
?>
				<a class=message title="send <?php echo $u->DisplayName; ?> a private message" href="/user/messages.php#!to=<?php echo htmlspecialchars($u->Username); ?>">send message</a>
<?php
			}
			if($user->IsLoggedIn())
				if($u->ID == $user->ID) {
?>
				<a class=edit title="edit your profile" href="/user/settings.php">edit profile</a>
<?php
				} elseif($u->Fan) {
?>
				<a class=removefriend title="remove <?php echo $u->DisplayName; ?> from your friends" href="/user/?ajax=removefriend&amp;friend=<?php echo $u->ID; ?>">remove friend</a>
<?php
				} else {
?>
				<a class=addfriend title="add <?php echo $u->DisplayName; ?> as a friend" href="/user/?ajax=addfriend&amp;friend=<?php echo $u->ID; ?>">add friend</a>
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
				<a href="<?php echo htmlspecialchars($link['url']); ?>" title="<?php echo $link['title']; ?>"><img src="/images/contact/<?php echo $link['type']; ?>.png" alt="<?php echo $link['type']; ?>"></a>
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
					<li>#<?php echo Rank('fans', $stats->fans); ?> in fans with <?php echo $stats->fans; ?></li>
<?php
			}
			if($stats->comments) {
?>
					<li>#<?php echo Rank('comments', $stats->comments); ?> in <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/<?php echo $u->Username; ?>/comments" title="view all of <?php echo $u->Username; ?>’s comments">comments</a> with <?php echo $stats->comments; ?></li>
<?php
			}
			if($stats->replies) {
?>
					<li>#<?php echo Rank('replies', $stats->replies); ?> in <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/<?php echo $u->Username; ?>/replies" title="view all of <?php echo $u->Username; ?>’s forum posts">forum posts</a> with <?php echo $stats->replies; ?></li>
<?php
			}
?>
				</ul>
			</section>
<?php
		}
?>
			<section id=activity>
				<p data-bind="visible: !loading() && activity().length < 1"><?php echo $u->DisplayName; ?> hasn’t posted anything to track7 yet.</p>
				<ol data-bind="foreach: activity">
					<li data-bind="css: conttype">
						<span class=action data-bind="text: action"></span>
						<a data-bind="text: title, attr: {href: url}"></a>
						<time data-bind="text: posted.display, attr: {datetime: posted.datetime, title: posted.title}"></time>
						ago
					</li>
				</ol>
				<p class=loading data-bind="visible: loading">loading more activity...</p>
				<p class="more calltoaction" data-bind="visible: !loading() && more()"><a href=#activity class="action get" data-bind="click: Load">show more activity from <?php echo $u->DisplayName; ?></a></p>
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
			if($r = $db->query('select count(1) as rank from users_stats where ' . $stat . '>=' . +$value))
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
