import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { currentUser } from "user";
import MessageApi from "/api/message.js";
import UserApi from "/api/user.js";

const UserField = {
	data() {
		return {
			usermatch: "",
			matchingusers: [],
			findingusers: false,
			cursor: null
		};
	},
	emits: ["selectUser"],
	watch: {
		usermatch(match) {
			if(this.waitUserSuggest)
				clearTimeout(this.waitUserSuggest);
			delete this.waitUserSuggest;
			if(match.length < 3) {
				this.matchingusers = [];
				this.findingusers = false;
			} else {
				this.waitUserSuggest = setTimeout(async () => {
					this.findingusers = true;
					try {
						const matches = await UserApi.suggest(match);
						this.matchingusers = matches;
					} catch(error) {
						alert(error.message);
					} finally {
						this.findingusers = false;
					}
				}, 250);
			}
		}
	},
	created() {
		this.SelectHashUser();
	},
	methods: {
		async SelectHashUser() {
			if(location.hash.substring(0, 5) == "#!to=")
				try {
					const user = await UserApi.info(location.hash.substring(5));
					this.Select(user);
				} catch {
					this.usermatch = location.hash.substring(5);
				}
		},
		HideSuggestions(delay) {
			setTimeout(() => {
				this.usermatch = "";
				this.cursor = false;
			}, +delay);
		},
		Next() {
			if(this.cursor)
				for(var u = 0; u < this.matchingusers.length - 1; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.cursor = this.matchingusers[u + 1];
						return;
					}
			this.cursor = this.matchingusers[0];
		},
		Prev() {
			if(this.cursor)
				for(var u = 1; u < this.matchingusers.length; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.cursor = this.matchingusers[u - 1];
						return;
					}
			this.cursor = this.matchingusers[this.matchingusers.length - 1];
		},
		SelectCursorUser() {
			if(this.cursor)
				for(var u = 0; u < this.matchingusers.length; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.Select(this.cursor);
						return;
					}
		},
		Select(user) {
			this.usermatch = "";
			this.cursor = null;
			this.$emit("selectUser", user);
		}
	},
	template: /* html */ `
		<input id=usermatch placeholder="find a person" autocomplete=off autofocus v-model=usermatch @blur=HideSuggestions(250) @keydown.esc=HideSuggestions @keydown.down.prevent=Next @keydown.up.prevent=Prev @keydown.enter.prevent=SelectCursorUser @keydown.tab=SelectCursorUser>
		<ol class=usersuggest v-if="usermatch.length >= 3">
			<li v-if=findingusers>finding people...</li>
			<li class=suggesteduser v-for="user in matchingusers" :class="{highlight: user.ID == cursor?.ID}" @click.prevent=Select(user)>
				<img class=avatar alt="" :src=user.Avatar>
				<span class=username :class="{friend: user.Friend}" :title="user.Friend ? user.DisplayName + ' is your friend' : null">{{user.DisplayName}}</span>
			</li>
			<li v-if="!findingusers && matchingusers.length < 1">nobody here by that name</li>
		</ol>
	`
};

if(document.querySelector("#sendmessage"))
	createApp({
		name: "SendMessage",
		data() {
			return {
				user: null,
				fromname: "",
				fromcontact: "",
				message: "",
				sending: false,
				error: "",
				sentmessages: []
			};
		},
		computed: {
			canSend() {
				return !this.sending && this.user && this.message.length > 1;
			}
		},
		async created() {
			await nextTick();
			autosize(this.$refs.message);
		},
		methods: {
			SelectUser(user) {
				this.user = user;
				this.$refs.fromname?.focus();
			},
			async Send() {
				this.sending = true;
				this.error = "";
				try {
					const message = await MessageApi.send(this.user.ID, this.message, this.fromname, this.fromcontact);
					this.message = "";
					this.sentmessages.push(message);
					await nextTick();
					Prism.highlightAll();
				} catch(error) {
					this.error = error.message;
				} finally {
					this.sending = false;
				}
			},
			async ClearUser() {
				if(history.replaceState)
					history.replaceState(null, null, location.pathname);
				else
					location.hash = "";
				this.user = null;
				nextTick();
				document.querySelector("#usermatch")?.focus();
			}
		},
		template: /* html */ `
			<form @submit.prevent=Send>
				<label title="user who will receive this message">
					<span class=label>to:</span>
					<span class="suggest field">
						<UserField @selectUser=SelectUser v-if=!user />
						<span v-if=user>
							<img class="avatar inline" :src=user.Avatar>
							<a class=user :href="'/user/' + user.Username + '/'">{{user.DisplayName}}</a>
							<a class="action del" @click.prevent=ClearUser :title="'remove ' + user.DisplayName + ' and choose someone else'"></a>
						</span>
					</span>
				</label>
				<label title="your name">
					<span class=label>from:</span>
					<span class=field><input maxlength=48 ref=fromname v-model=fromname placeholder="random internet person"></span>
				</label>
				<label title="e-mail address or url where replies can be sent (optional)">
					<span class=label>contact:</span>
					<span class=field><input maxlength=255 v-model=fromcontact></span>
				</label>
				<label class=multiline title="message text you would like to send (markdown allowed)">
					<span class=label>message:</span>
					<span class=field><textarea v-model=message ref=message></textarea></span>
				</label>
				<button :disabled=!canSend :class="{working: sending}">send</button>

				<p class=error v-if=error>{{error}}</p>

				<template v-for="msg in sentmessages">
					<h2>message sent <time :datetime=msg.Instant.DateTime v-html=msg.Instant.Display></time></h2>
					<div v-html=msg.HTML></div>
				</template>
	</form>
		`
	}).component("UserField", UserField)
		.mount("#sendmessage");

const Messages = {
	props: [
		"conv"
	],
	emits: [
		"messageSent"
	],
	data() {
		return {
			response: "",
			sending: false,
			sendError: ""
		};
	},
	computed: {
		loading() {
			return this.conv.loading;
		},
		canSend() {
			return !this.sending && this.response.length > 1;
		}
	},
	watch: {
		async loading(newVal, oldVal) {
			if(!newVal && oldVal)
				await nextTick();
			Prism.highlightAll();
			const visibleMessageContainer = firstVisible(document.querySelectorAll(".messages"));
			const shownMessages = visibleMessageContainer.querySelectorAll("li");
			let index = 0;
			if(!this.lastScrollCount) {
				const firstUnread = this.conv.messages.findIndex(m => m.Unread && !m.Outgoing);
				if(firstUnread > -1)
					index = firstUnread;
				else
					index = this.conv.messages.length - 1;
			} else
				index = this.conv.messages.length - this.lastScrollCount;
			timedScrollTo(shownMessages[index], 750);
			if(!this.lastScrollCount)
				this.$refs.response.focus();
			this.lastScrollCount = this.conv.messages.length;
		}
	},
	async created() {
		await nextTick();
		autosize(this.$refs.response);
		if(!this.conv.loading)
			this.$refs.response.focus();
	},
	methods: {
		async Load() {
			this.conv.loading = true;
			this.conv.error = "";
			try {
				const result = await MessageApi.conversation(this.conv.With.ID, this.conv.messages.length);
				this.conv.messages = [...result.Messages, ...this.conv.messages];
				this.conv.hasMore = result.HasMore;
			} catch(error) {
				this.conv.error = error.message;
			} finally {
				this.conv.loading = false;
			}
		},
		async Reply() {
			this.sending = true;
			this.sendError = "";
			try {
				const message = await MessageApi.send(this.conv.With.ID, this.response);
				this.conv.messages.push(message);
				this.conv.Instant.Display = "now";
				this.conv.Instant.DateTime = message.Instant.DateTime;
				this.conv.Instant.Tooltip = message.Instant.Display.replaceAll(/<\/?sup>/g, "");
				this.$emit("messageSent", message);
				this.response = "";
				await nextTick();
				Prism.highlightAll();
			} catch(error) {
				this.sendError = error.message;
			} finally {
				this.sending = false;
			}
		}
	},
	template: /* html */ `
		<ol class=messages>
			<li class=error v-if=conv.error>{{conv.error}}</li>
			<li class=loading v-if=conv.loading>loading messages...</li>
			<li class="showmore calltoaction" v-if=conv.hasMore>
				<a class="action get" @click.prevent=Load href="#!LoadMessages">load older messages</a>
			</li>
			<li v-for="msg in conv.messages" :class="{outgoing: msg.Outgoing, incoming: !msg.Outgoing}" :key=msg.ID>
				<div class=userinfo>
					<div class=username v-if="!msg.Outgoing && !conv.With.ID && !msg.Contact">{{msg.Name}}</div>
					<div class=username v-if="!msg.Outgoing && !conv.With.ID && msg.Contact"><a :href=msg.Contact>{{msg.Name}}</a></div>
					<div class=username v-if="!msg.Outgoing && conv.With.URL"><a :href=conv.With.URL>{{conv.With.Name}}</a></div>
					<div class=username v-if=msg.Outgoing><a href=${currentUser?.URL}>${currentUser?.DisplayName}</a></div>
					<img class=avatar v-if="!msg.Outgoing && conv.With.Avatar" :src=conv.With.Avatar>
					<img class=avatar v-if=msg.Outgoing src="${currentUser?.Avatar}">
				</div>
				<div class=message>
					<header>sent <time :datetime=msg.Instant.DateTime v-html=msg.Instant.Display></time></header>
					<div class=content v-html=msg.HTML></div>
				</div>
			</li>
			<li class="outgoing reply" v-if=conv.With.ID>
				<div class=userinfo>
					<div class=username><a href=${currentUser?.Profile}>${currentUser?.DisplayName}</a></div>
					<img class=avatar src="${currentUser?.Avatar}">
				</div>
				<div class=message>
					<form class=reply @submit.prevent=Reply>
						<label class=multiline title="message text you would like to send (markdown allowed)">
							<span class=label>reply:</span>
							<span class=field><textarea ref=response v-model=response :placeholder="'write to ' + conv.With.Name"></textarea></span>
						</label>
						<button :disabled=!canSend :class="{working: sending}">send</button>
					</form>
				</div>
			</li>
		</ol>
	`
};

if(document.querySelector("#messages"))
	createApp({
		name: "Conversations",
		data() {
			return {
				conversations: [],
				selected: null,
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
					const conversations = await MessageApi.list();
					this.conversations = conversations;
				} catch(error) {
					this.error = error.message;
				} finally {
					this.loading = false;
				}
			},
			SelectUser(user) {
				const existingConv = this.conversations.find(c => c.With.ID == user.ID);
				if(existingConv)
					this.Select(existingConv);
				else {
					const newConv = {
						With: {
							ID: user.ID,
							Name: user.DisplayName,
							Avatar: user.Avatar
						},
						Instant: {
							DateTime: new Date().toISOString(),
							Display: 'new',
							Tooltip: 'no message sent yet'
						},
						Unread: false,
						Replied: true,
						messages: [],
						hasMore: false
					};
					this.conversations.unshift(newConv);
					this.Select(newConv);
				}
			},
			async Select(conv) {
				this.selected = conv;
				if(!conv.messages) {
					conv.loading = true;
					try {
						const result = await MessageApi.conversation(conv.With.ID);
						conv.messages = result.Messages;
						conv.hasMore = result.HasMore;
					} catch(error) {
						conv.error = error.message;
					} finally {
						conv.loading = false;
					}
				}
			},
			MessageSent() {
				this.conversations.sort((a, b) => a.Instant.DateTime < b.Instant.DateTime ? 1 : -1);
			}
		},
		template: /* html */ `
			<div id=conversations>
				<form id=sendtouser>
					<label title="search for a user to send a message to"><span class="suggest field">
						<UserField @selectUser=SelectUser />
					</span></label>
				</form>
				<p class=loading v-if=loading>loading conversations...</p>
				<p class=error v-if=error>{{error}}</p>
				<p v-if="!loading && conversations.length<1">no messages sent or received so far.  find a user you’d like to start a conversation with to change that.</p>
				<ol class=conversations>
					<li v-for="conv in conversations" :key=conv.With.ID :class="{selected: selected?.With.ID == conv.With.ID}">
						<header :class="{read: !conv.Unread, outgoing: conv.Replied, incoming: !conv.Replied}" @click.prevent="Select(conv)"><img class=avatar :src=conv.With.Avatar><span>{{conv.With.Name}}</span><time :datetime=conv.Instant.DateTime :title=conv.Instant.Tooltip>{{conv.Instant.Display}}</time></header>
						<Messages v-if="selected?.With.ID == conv.With.ID" :conv=conv @messageSent=MessageSent />
					</li>
				</ol>
			</div>

			<div id=conversationpane v-if=selected>
				<Messages :conv=selected @messageSent=MessageSent />
			</div>
		`
	}).component("UserField", UserField)
		.component("Messages", Messages)
		.mount("#messages");

function firstVisible(elements) {
	for(const element of elements)
		if(element.checkVisibility())
			return element;
}

function timedScrollTo(element, duration) {
	const startY = window.pageYOffset;
	const scrollDistance = element.getBoundingClientRect().top;
	let startTime = null;
	function animationStep(now) {
		if(!startTime)
			startTime = now;
		const elapsedPortion = Math.min((now - startTime) / duration, 1);
		const elapsedDistance = .5 - Math.cos(elapsedPortion * Math.PI) / 2;
		window.scrollTo(0, startY + scrollDistance * elapsedDistance);
		if(elapsedPortion < 1)
			requestAnimationFrame(animationStep);
	}
	requestAnimationFrame(animationStep);
}
