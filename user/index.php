<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true]);
$html->Open('user list');
?>
			<h1>
				users by
				<span class=sortoption>
					<a class=droptrigger :href="'#' + sort.key" v-on:click.stop.prevent=ShowSortOptions>{{sort.display}}</a>
					<span class=droplist>
						<a v-for="opt in sortoptions" v-if="sort != opt" :href="'#' + opt.key" v-on:click.stop.prevent=Sort(opt)>{{opt.display}}</a>
					</span>
				</span>
			</h1>

			<p class=info v-if=loading>loading user list...</p>
			<p class=info v-if="!loading && users.length == 0">no users found</p>

			<ol id=userlist v-if=users.length>
				<li v-for="user in users">
					<header>
						<div class=username :class="{friend: user.friend}" :title="user.friend ? user.displayname + ' is your friend' : null"><a :href="user.username + '/'" :title="'view ' + user.displayname + '’s profile'">{{user.displayname || user.username}}</a></div>
						<div class=userlevel>{{user.levelname}}</div>
					</header>
					<div>
						<a class=avatar :href="user.username + '/'"><img class=avatar alt="" :src=user.avatar></a>
						<div class=userstats>
							<time class=lastlogin :datetime=user.lastlogin.datetime :title="'last signed in ' + user.lastlogin.title">{{user.lastlogin.display + ' ago'}}</time>
							<time class=joined :datetime=user.registered.datetime :title="'joined ' + user.registered.title">{{user.registered.display + ' ago'}}</time>
							<div class=counts>
								<div class=fans v-if=+user.fans title="user.fans + (user.fans > 1 ? ' people call ' : ' person calls ') + user.displayname + ' a friend'">{{user.fans}}</div>
								<div class=comments v-if=+user.comments title="user.displayname + ' has posted ' + user.comments + (user.comments > 1 ? ' comments' : ' comment')">{{user.comments}}</div>
								<div class=forum v-if=+user.replies title="user.displayname + ' has posted ' + user.replies + (user.replies > 1 ? ' forum replies' : ' forum reply')}">{{user.replies}}</div>
							</div>
						</div>
					</div>
				</li>
			</ol>
<?php
$html->Close();
