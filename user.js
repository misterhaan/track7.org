import { createApp } from "vue";
import { popup } from "popup";
import UserApi from "/api/user.js";

const userLink = document.querySelector("#whodat");
const loginMenu = document.querySelector("#loginmenu");

export const currentUser = userLink ? {
	URL: userLink.href,
	DisplayName: userLink.textContent.trim(),
	Avatar: userLink.querySelector("img")?.src,
	Level: userLink.dataset.level,
} : null;

popup.register(loginMenu, "#signin");
popup.register("#usermenu", userLink);

document.querySelector("#logoutlink")?.addEventListener("click", async event => {
	event.preventDefault();
	event.stopPropagation();
	try {
		await UserApi.logout();
		location.reload(false);
	} catch(error) {
		alert(error.message);
	}
});


loginMenu && createApp({
	name: "LoginMenu",
	data() {
		return {
			provider: null,
			authProviders: ["google", "twitter", "github", "deviantart", "steam", "twitch", "track7"],
			username: "",
			password: "",
			remember: false,
			working: false,
			error: ""
		};
	},
	computed: {
		loginCaption() {
			if(this.provider)
				if(this.provider == "track7")
					return "sign in with track7 password";
				else
					return "sign in with " + this.provider;
			return "choose site to sign in through";
		},
		loginDisabled() {
			return this.provider == null || (this.provider == "track7" && (!this.username || !this.password));
		}
	},
	methods: {
		TryLogin(provider) {
			if(provider != this.provider)
				this.provider = provider;
			if(!this.loginDisabled)
				this.Login();
		},
		async Login() {
			this.working = true;
			this.error = "";
			if(this.provider == "track7")
				try {
					await UserApi.login(this.username, this.password, this.remember);
					location.reload();
				} catch(error) {
					this.error = error.message;
				} finally {
					this.working = false;
				}
			else
				try {
					const redirectURL = await UserApi.auth(this.provider, this.remember)
					location = redirectURL;
				} catch(error) {
					this.error = error.message;
				} finally {
					this.working = false;
				}
		}
	},
	template: /* html */ `
		<form id=signinform @submit.prevent=Login>
			sign in securely with your account from one of these sites:
			<div id=authchoices>
				<label v-for="auth in authProviders" :key=auth :class="auth + (provider==auth ? ' selected' : '')" :title="'sign in with your ' + auth + ' account'" tabindex=0 @keydown.space.stop.prevent="provider = auth" @keydown.enter.stop.prevent=TryLogin(auth)>
					<input type=radio :value=auth v-model=provider>
				</label>
			</div>
			<div id=oldlogin v-if="provider == 'track7'">
				note: this is only for users who have already set up a password.
				<label>username: <input name=username v-model.trim=username required maxlength=32></label>
				<label>password: <input name=password v-model.trim=password required type=password></label>
			</div>
			<label class=checkbox><input type=checkbox v-model=remember> remember me</label>
			<button :disabled=loginDisabled>{{loginCaption}}</button>
			<p class=error v-if="error">{{error}}</p>
		</form>
	`
}).mount("#loginmenu");
