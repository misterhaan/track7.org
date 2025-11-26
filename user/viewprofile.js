import { createApp } from "vue";
import ActivityApi from "/api/activity.js";
import ContactApi from "/api/contact.js";
import UserApi from "/api/user.js";

const username = location.pathname.split("/").filter(n => n).pop();
const displayname = document.querySelector("h1").innerText;

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
		async Load() {
			this.loading = true;
			try {
				const result = await ContactApi.list(username);
				this.contacts = result;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		}
	},
	template: /* html */ `
		<p v-if="loading" class="loading">loading contacts</p>
		<p v-if="error" class="error">{{error}}</p>

		<a v-for="contact in contacts" :class=contact.Type :href=contact.URL :title=contact.Action></a>
	`
}).mount("#contacts");

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
		async Load() {
			this.loading = true;
			try {
				const result = await ActivityApi.byuser(username, this.activity.length);
				this.activity = this.activity.concat(result.Activity);
				this.hasMore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
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
		<p class="more calltoaction" v-if="!loading && hasMore"><a href=#activity class="action get" @click=Load>show more activity from ${displayname}</a></p>
	`,
	compilerOptions: {
		whitespace: "preserve"  // otherwise, whitespace between elements in the activity line is removed
	}
}).mount("#activity");

document.querySelector("a.addfriend")?.addEventListener("click", friend);
document.querySelector("a.removefriend")?.addEventListener("click", friend);

async function friend(event) {
	event.preventDefault();
	event.stopPropagation();

	const method = getApiMethodFromClass(this.className);
	const id = this.href.split("/").filter(n => n).pop();
	try {
		await method(id);
		toggleFriendship(this);
	} catch(error) {
		alert(error.message);
	}
}

function getApiMethodFromClass(className) {
	switch(className) {
		case "addfriend":
			return UserApi.putFriend;
		case "removefriend":
			return UserApi.deleteFriend;
	}
}

function toggleFriendship(link) {
	const h1 = document.querySelector("h1");
	switch(link.className) {
		case "addfriend":
			h1.classList.add("friend");
			link.classList.remove("addfriend");
			link.classList.add("removefriend");
			link.innerText = "remove friend";
			link.title = "remove " + displayname + " from your friends list";
			break;
		case "removefriend":
			h1.classList.remove("friend");
			link.classList.remove("removefriend");
			link.classList.add("addfriend");
			link.innerText = "add friend";
			link.title = "add " + displayname + " to your friends list";
			break;
	}
}
