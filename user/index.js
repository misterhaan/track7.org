import "jquery";
import { createApp } from "vue";

document.popup = [];

const sortoptions = [
	{ key: "lastlogin", display: "latest sign-in" },
	{ key: "joined", display: "join date" },
	{ key: "userlevel", display: "level" },
	{ key: "username", display: "name" }
];

const sortfunction = {
	lastlogin(left, right) {
		return left.LastLogin.DateTime == right.LastLogin.DateTime ? 0 : left.LastLogin.DateTime < right.LastLogin.DateTime ? 1 : -1;
	},
	joined(left, right) {
		return left.Registered.DateTime == right.Registered.DateTime ? 0 : left.Registered.DateTime < right.Registered.DateTime ? -1 : 1;
	},
	userlevel(left, right) {
		return right.Level - left.Level;
	},
	username(left, right) {
		return left.Username.toLowerCase() < right.Username.toLowerCase() ? -1 : 1;
	},
};

if($("#whodat").length) {
	sortoptions.push({ key: "friends", display: "friendship" });
	sortfunction.friends = function(left, right) {
		return right.Friend - left.Friend;
	};
}

createApp({
	name: "UserIndex",
	data() {
		return {
			users: [],
			sortoptions: sortoptions,
			sort: sortoptions[0],
			loading: false,
			hasMore: false,
			error: ""
		};
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/user.php/list/" + this.users.length).done(result => {
				this.users = this.users.concat(result.Users);
				this.hasmore = result.HasMore;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		ShowSortOptions(e) {
			const visdrop = $(e.target).siblings(".droplist");
			if(document.popup.length && document.popup[0][0] == visdrop[0]) {
				document.popup.shift().hide();
			} else {
				if(document.popup.length)
					document.popup[0].hide();
				document.popup.unshift(visdrop);
				document.popup[0].show();
			}
		},
		Sort(option) {
			this.sort = option;
			this.users.sort(sortfunction[this.sort.key]);
			if(document.popup.length)
				document.popup.shift().hide();
		}
	},
	template: /* html */ `
		<h1>
			users by
			<span class=sortoption>
				<a class=droptrigger :href="'#' + sort.key" @click.stop.prevent=ShowSortOptions>{{sort.display}}</a>
				<span class=droplist>
					<template v-for="opt in sortoptions">
						<a v-if="sort != opt" :href="'#' + opt.key" @click.stop.prevent=Sort(opt)>{{opt.display}}</a>
					</template>
				</span>
			</span>
		</h1>

		<ol id=userlist v-if=users.length>
			<li v-for="user in users">
				<header>
					<div class=username :class="{friend: user.Friend}" :title="user.Friend ? user.DisplayName + ' is your friend' : null"><a :href="user.Username + '/'" :title="'view ' + user.DisplayName + '’s profile'">{{user.DisplayName}}</a></div>
					<div class=userlevel>{{user.LevelName}}</div>
				</header>
				<div>
					<a class=avatar :href="user.Username + '/'"><img class=avatar alt="" :src=user.Avatar></a>
					<div class=userstats>
						<time class=lastlogin :datetime=user.LastLogin.DateTime :title="'last signed in ' + user.LastLogin.Tooltip">{{user.LastLogin.Display + ' ago'}}</time>
						<time class=joined :datetime=user.Registered.DateTime :title="'joined ' + user.Registered.Tooltip">{{user.Registered.Display + ' ago'}}</time>
						<div class=counts>
							<div class=fans v-if=user.Fans.Count :title="user.Fans.Count + (user.Fans.Count > 1 ? ' people call ' : ' person calls ') + user.DisplayName + ' a friend'">{{user.Fans.Count}}</div>
							<div class=posts v-if=+user.Posts.Count :title="user.DisplayName + ' has created ' + user.Posts.Count + (user.Posts.Count > 1 ? ' posts' : ' post')">{{user.Posts.Count}}</div>
							<div class=comments v-if=user.Comments.Count :title="user.DisplayName + ' has posted ' + user.Comments.Count + (user.Comments.Count > 1 ? ' comments' : ' comment')">{{user.Comments.Count}}</div>
						</div>
					</div>
				</div>
			</li>
		</ol>

		<p class=loading v-if=loading>loading user list...</p>
		<p class=info v-if="!loading && users.length == 0">no users found</p>
	`
}).mount("main");
