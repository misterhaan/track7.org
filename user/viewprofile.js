import "jquery";
import { createApp } from "vue";

const username = document.location.pathname.split("/").filter(n => n).pop();
const displayname = $("h1").text();

createApp({
	name: "Contact",
	data() {
		return {
			contacts: [],
			loading: false,
			error: ""
		};
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/contact.php/list/" + username).done(result => {
				this.contacts = result;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		}
	},
	template: /* html */ `
		<p v-if="loading" class="loading">loading contacts</p>
		<p v-if="error" class="error">{{error}}</p>

		<a v-for="contact in contacts" :class=contact.Type :href=contact.URL :title=contact.Action></a>
	`
}).mount("#contact");

createApp({
	name: "Activity",
	data() {
		return {
			activity: [],
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
			$.get("/api/activity.php/byuser/" + username + "/" + this.activity.length).done(result => {
				this.activity = this.activity.concat(result.Activity);
				this.hasMore = result.HasMore;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		}
	},
	template: /* html */ `
		<p v-if="!loading && activity.length < 1">${displayname} hasn’t posted anything to track7 yet.</p>
		<ol>
			<li v-for="act in activity" :class=act.Type>
				<span class=action>{{act.Verb}}</span>
				<a :href=act.URL>{{act.Title}}</a>
				<time :datetime=act.Instant.DateTime :title=act.Instant.Tooltip>{{act.Instant.Display}}</time>
				ago
			</li>
		</ol>
		<p class=loading v-if=loading>loading more activity...</p>
		<p class=error v-if=error>{{error}}</p>
		<p class="more calltoaction" v-if="!loading && hasMore"><a href=#activity class="action get" v-on:click=Load>show more activity from ${displayname}</a></p>
	`,
	compilerOptions: {
		whitespace: "preserve"  // otherwise, whitespace between elements in the activity line is removed
	}
}).mount("#activity");

$("a.addfriend").click(friend);
$("a.removefriend").click(friend);

function friend() {
	const method = getApiMethodFromClass(this.className);
	$.ajax({
		url: this.href,
		type: method,
	}).done(() => {
		toggleFriendship(this);
	}).fail(request => {
		alert(request.responseText);
	});
	return false;
}

function getApiMethodFromClass(className) {
	switch(className) {
		case "addfriend":
			return "PUT";
		case "removefriend":
			return "DELETE";
	}
}

function toggleFriendship(link) {
	const h1 = $("h1");
	switch(link.className) {
		case "addfriend":
			h1.addClass("friend");
			$(link)
				.removeClass("addfriend")
				.addClass("removefriend")
				.text("remove friend")
				.attr("title", "remove " + displayname + " from your friends list");
			break;
		case "removefriend":
			h1.removeClass("friend");
			$(link)
				.removeClass("removefriend")
				.addClass("addfriend")
				.text("add friend")
				.attr("title", "add " + displayname + " to your friends list");
			break;
	}
}
