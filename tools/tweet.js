import { createApp } from "vue";
import ToolApi from "/api/tool.js";

createApp({
	name: "Tweet",
	data() {
		return {
			authStatus: null,
			authorizing: false,
			error: "",
			message: "",
			url: "",
			tweeting: false,
			response: null
		};
	},
	computed: {
		needsAuth() {
			return this.authStatus && (!this.authStatus.Refresh.Exists || this.authStatus.Refresh.Expired);
		},
		hasAuth() {
			return this.authStatus && (this.authStatus.Access.Exists && !this.authStatus.Access.Expired || this.authStatus.Refresh.Exists && !this.authStatus.Refresh.Expired);
		},
		accessTokenStatus() {
			if(!this.authStatus)
				return '';
			if(!this.authStatus.Access.Exists)
				return 'access token not found.';
			if(this.authStatus.Access.Expired)
				return 'access token expired.';
			return this.authStatus.Access.ExpiresIn + ' remaining on access token.';
		},
		refreshTokenStatus() {
			if(!this.authStatus)
				return '';
			if(!this.authStatus.Refresh.Exists)
				return 'refresh token not found.';
			if(this.authStatus.Refresh.Expired)
				return 'refresh token expired.';
			return this.authStatus.Refresh.ExpiresIn + ' remaining on refresh token.';
		},
		canTweet() {
			return this.hasAuth && !this.tweeting && this.message.trim().length;
		}
	},
	async created() {
		const query = new URLSearchParams(location.search);
		const code = query.get("code");
		const csrf = query.get("state");
		if(code && csrf)
			try {
				const result = await ToolApi.tweetAuth(code, csrf);
				this.authStatus = result;
				history.replaceState({}, document.title, location.pathname);  // clear the query parameters
			} catch(error) {
				this.error = error.message;
			}
		else {
			const status = await ToolApi.tweetAuthStatus()
			this.authStatus = status;
		}
	},
	methods: {
		async RequestAuth() {
			if(!this.authorizing) {
				this.authorizing = true;
				try {
					const url = await ToolApi.tweetAuthURL();
					location.href = url;
				} catch(error) {
					alert(error.message);
					this.authorizing = false;
				}
			}
		},
		async Tweet() {
			this.tweeting = true;
			try {
				const response = await ToolApi.tweet(this.message, this.url);
				this.response = response;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.tweeting = false;
			}
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>
		<section v-if="needsAuth">
			<h2>authorize tweeting</h2>
			<p>
				before track7 can tweet site activity, the @track7feed account needs to
				authorize it.  bounce over to sign in and it’ll bounce you back here
				where you can send a test tweet.
			</p>
			<p class=calltoaction>
				<a href="/api/tool.php/tweetAuthURL" class="twitter action" :class="{working: authorizing}" :disabled=authorizing @click.prevent=RequestAuth>sign in to @track7feed</a>
			</p>
		</section>
		<section v-if="hasAuth">
			<h2>status</h2>
			<p>
				{{accessTokenStatus}}
				{{refreshTokenStatus}}
				track7 should be ready to tweet but you can re-authorize if it’s not working right.
			</p>
			<p class=calltoaction>
				<a href="/api/tool.php/tweetAuthURL" class="twitter action" :class="{working: authorizing}" :disabled=authorizing @click.prevent=RequestAuth>sign in to @track7feed</a>
			</p>

			<h2>test tweeting</h2>
			<p>
				anything entered into this form gets sent to <a href="https://twitter.com/track7feed">twitter</a>
				(even from the test site), so remember to delete test tweets.
			</p>
			<form method=post @submit.prevent=Tweet>
				<label title="enter a message to tweet">
					<span class=label>message:</span>
					<span class=field><input name=message v-model=message></span>
				</label>
				<label title="enter a url to send with the tweet (optional)">
					<span class=label>url:</span>
					<span class=field><input name=url v-model=url></span>
				</label>
				<button :disabled=!canTweet :class="{working: tweeting}">tweet</button>
			</form>

			<section v-if=response>
				<h2>response code {{response.code}}</h2>
				<pre><code>{{response.text}}</code></pre>
			</section>
		</section>
	`
}).mount("#tweet");
