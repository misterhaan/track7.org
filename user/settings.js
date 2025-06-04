import "jquery";
import { createApp } from "vue";
import { ValidatingField } from "validate";
import { popup } from "popup";
import { currentUser } from "user";

const contactTypes = [
	{ id: "email", name: "e-mail", msgChecking: "validating e-mail address...", msgValid: "valid e-mail address", msgBlank: "e-mail address will not be included", help: "address at which track7 and (if you choose) users and / or visitors can contact you" },
	{ id: "website", name: "website", msgChecking: "validating website url...", msgValid: "website url exists", msgBlank: "website link will not be included", help: "url of your personal website" },
	{ id: "twitter", name: "twitter", msgChecking: "validating twitter username...", msgValid: "valid twitter handle", msgBlank: "twitter link will not be included", help: "your twitter handle (without the @)" },
	{ id: "facebook", name: "facebook", msgChecking: "validating facebook username...", msgValid: "valid facebook profile", msgBlank: "facebook link will not be included", help: "your facebook username" },
	{ id: "github", name: "github", msgChecking: "validating github username...", msgValid: "valid github profile", msgBlank: "github link will not be included", help: "your github username" },
	{ id: "deviantart", name: "deviantart", msgChecking: "validating deviantart username...", msgValid: "valid deviantart profile", msgBlank: "deviantart link will not be included", help: "your deviantart username" },
	{ id: "steam", name: "steam", msgChecking: "validating steam profile...", msgValid: "valid steam profile", msgBlank: "steam link will not be included", help: "your steam profile" },
	{ id: "twitch", name: "twitch", msgChecking: "validating twitch username...", msgValid: "valid twitch username", msgBlank: "twitch link will not be included", help: "your twitch username" },
];

const Profile = {
	data() {
		return {
			username: "",
			displayname: "",
			avatarID: "",
			avatarOptions: [],
			loading: false,
			error: "",
			saving: false,
			showSaveSuccess: false,
			invalidFields: new Set(),
		};
	},
	computed: {
		canSave: function() {
			return !this.loading && !this.saving && this.invalidFields.size <= 0;
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/settings.php/profile").done(result => {
				this.username = result.Username;
				this.displayname = result.DisplayName;
				this.avatarID = result.AvatarOptions.find(o => o.ImageURL == result.Avatar)?.ID;;
				this.avatarOptions = result.AvatarOptions;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this[fieldName] = newValue;
		},
		Save() {
			if(!this.canSave)
				alert("address errors before saving.");
			else {
				this.saving = true;
				this.error = "";
				$.post("/api/settings.php/profile", {
					username: this.username,
					displayname: this.displayname,
					avatarsource: this.avatarID
				}).done(() => {
					this.showSaveSuccess = true;
					setTimeout(() => {
						this.showSaveSuccess = false;
					}, 2000);

					$("a[href='" + $("#whodat").attr("href") + "']").attr("href", "/user/" + this.username + "/");
					$("#whodat").contents().get(0).nodeValue = this.displayname || this.username;
					if(this.avatarID)
						$("#whodat img.avatar").attr("src", this.avatarOptions.find(o => o.ID == this.avatarID).ImageURL);
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.saving = false;
				});
			}
		}
	},
	template: /* html */ `
		<form class=tabcontent id=profile @submit.prevent=Save>
			<p class=error v-if=error>{{error}}</p>
			<label title="used in the url to your profile">
				<span class=label>username:</span>
				<ValidatingField :value=username validateUrl="/api/user.php/idAvailable"
					msgChecking="validating username..." msgValid="username available" msgBlank="username required"
					inputAttributes="{maxlength: 32, required: true, pattern: '[a-zA-Z0-9_\-]+'}"
					@validated="(isValid, newValue) => OnValidated('username', isValid, newValue)"
				></ValidatingField>
			</label>
			<label title="an easier to read name for when you comment, etc.  leave blank to just show your username">
				<span class=label>display name:</span>
				<ValidatingField :value=displayname :default=username validateUrl="/api/user.php/nameAvailable" :isBlankValid=true
					msgChecking="validating display name..." msgValid="display name available" msgBlank="username will be used for display"
					inputAttributes="{maxlength: 32}"
					@validated="(isValid, newValue) => OnValidated('displayname', isValid, newValue)"
				></ValidatingField>
			</label>
			<fieldset class=avatar>
				<legend>profile picture</legend>
				<label v-for="avatarOption in avatarOptions" :key=avatarOption.ID>
					<span class=field>
						<input name=avatar :value=avatarOption.ID v-model=avatarID type=radio><img :src=avatarOption.ImageURL class=avatar>
						<a v-if="avatarOption.ID == 'gravatar'" href=https://gravatar.com/>gravatar</a> {{avatarOption.ID == "gravatar" ? avatarOption.Label.replace("gravatar ", " ") : avatarOption.Label}}
					</span>
				</label>
			</fieldset>
			<div class=submit>
				<button class=save :disabled=!canSave :class="{working: saving}">save</button>
				<Transition name=fadeout><p class=success v-if=showSaveSuccess>saved successfully</p></Transition>
			</div>
		</form>
	`
};

const TimeZone = {
	data() {
		return {
			currentTime: "",
			dst: false,
			error: "",
			loading: false,
			saving: false,
			showSaveSuccess: false,
			invalidFields: new Set()
		};
	},
	computed: {
		canSave: function() {
			return !this.loading && !this.saving && this.invalidFields.size <= 0;
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/settings.php/time").done(result => {
				this.currentTime = result.CurrentTime;
				this.dst = result.DST;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this[fieldName] = newValue;
		},
		DetectTime() {
			const now = new Date();
			const hour = now.getHours();
			this.currentTime = (hour == 0 ? 12 : hour > 12 ? hour - 12 : hour) + (now.getMinutes() < 10 ? ":0" : ":") + now.getMinutes() + (hour >= 12 ? " pm" : " am");
			const jan = new Date(now.getFullYear(), 1, 1);
			const jul = new Date(now.getFullYear(), 7, 1);
			this.dst = jan.getTimezoneOffset() != jul.getTimezoneOffset();
		},
		Save() {
			if(!this.canSave)
				alert("address errors before saving.");
			else {
				this.saving = true;
				this.error = "";
				$.post("/api/settings.php/time", {
					currenttime: this.currentTime,
					dst: this.dst
				}).done(() => {
					this.showSaveSuccess = true;
					setTimeout(() => {
						this.showSaveSuccess = false;
					}, 2000);
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.saving = false;
				});
			}
		},
	},
	template: /* html */ `
		<form class=tabcontent id=timezone @submit.prevent=Save>
			<p class=error v-if=error>{{error}}</p>
			<label>
				<span class=label>current time:</span>
				<ValidatingField :value=currentTime validateUrl="/api/date.php/validateTime"
					msgChecking="validating time..." msgValid="time valid" msgBlank="time required"
					inputAttributes="{required: true}"
					@validated="(isValid, newValue) => OnValidated('currentTime', isValid, newValue)"
				></ValidatingField>
				<button id=detecttime title="detect the current time from your computer / tablet / phone" @click.prevent=DetectTime>detect</button>
			</label>
			<label><input type=checkbox v-model=dst> use daylight saving time</label>
			<div class=submit>
				<button :disabled=!canSave :class="{working: saving}">save</button>
				<Transition name=fadeout><p class=success v-if=showSaveSuccess>saved successfully</p></Transition>
			</div>
		</form>
	`
};

const Contact = {
	data() {
		return {
			contacts: [],
			error: "",
			loading: false,
			saving: false,
			showSaveSuccess: false,
			invalidFields: new Set()
		};
	},
	computed: {
		canSave() {
			return !this.loading && !this.saving && this.contacts.length > 0 && this.invalidFields.size <= 0;
		},
		availableContactTypes() {
			return contactTypes.filter(type => !this.contacts.some(contact => contact.type.id == type.id));
		},
		canAddContact() {
			return this.contacts.length < contactTypes.length;
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/settings.php/contacts").done(result => {
				this.contacts = result.map(contact => {
					return {
						type: this.availableContactTypes.find(type => type.id == contact.Type),
						value: contact.Contact,
						visibility: contact.Visibility
					};
				});
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			const contact = this.contacts.find(contact => contact.type.id == fieldName);
			if(contact)
				contact.value = newValue;
		},
		AddContact(event) {
			const typeid = event.target.value;
			const type = this.availableContactTypes.find(type => type.id == typeid);
			if(type) {
				this.contacts.push({ type: type, value: "", visibility: "friends" });
				event.target.value = null;
			}
		},
		Save() {
			this.saving = true;
			this.contacts = this.contacts.filter(contact => contact.value != "");
			$.ajax({
				method: "POST",
				url: "/api/settings.php/contacts",
				contentType: "text/plain; charset=utf-8",
				data: JSON.stringify(this.contacts.map(contact => {
					return {
						type: contact.type.id,
						value: contact.value,
						visibility: contact.visibility
					};
				}))
			}).done(() => {
				this.showSaveSuccess = true;
				setTimeout(() => {
					this.showSaveSuccess = false;
				}, 2000);
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form class=tabcontent id=contact @submit.prevent=Save>
			<p>
				while anyone can send you a message on track7, you can also provide
				other contact information here and control who can see it. you must
				provide an e-mail address if you want track7 to e-mail you (like
				when someone sends you a message), but you can set it not to display
				to anyone. all of this information is optional.
			</p>
			<p class=error v-if=error>{{error}}</p>
			<label v-for="contact in contacts" :key=contact.type.id :title="contact.type.help">
				<span class=label>
					<select v-if=canAddContact v-model=contact.type>
						<option selected :value=contact.type>{{contact.type.name}}:</option>
						<option v-for="type in availableContactTypes" key=type.id :value=type>{{type.name}}:</option>
					</select>
					{{canAddContact ? "" : contact.type.name + ":"}}
				</span>
				<span class=field>
					<ValidatingField :value=contact.value :validateUrl="'/api/contact.php/validate/' + contact.type.id" :isBlankValid=true
						:msgChecking=contact.type.msgChecking :msgValid=contact.type.msgValid :msgBlank=contact.type.msgBlank
						@validated="(isValid, newValue) => OnValidated(contact.type.id, isValid, newValue)"
					/>
					<VisibilitySelector :value=contact.visibility :includeNoneOption="contact.type.id == 'email'" @changed="newValue => contact.visibility = newValue"/>
				</span>
			</label>
			<label v-if=canAddContact title="add another contact method">
				<span class=label @change=AddContact><select>
					<option selected :value=null></option>
					<option v-for="type in availableContactTypes" key=type.id :value=type.id>{{type.name}}</option>
				</select></span>
			</label>
			<div class=submit>
				<button :disabled=!canSave :class="{working: saving}">save</button>
				<Transition name=fadeout><p class=success v-if=showSaveSuccess>saved successfully</p></Transition>
			</div>
		</form>
	`
};

const Notification = {
	data() {
		return {
			email: "",
			notifymsg: false,
			error: "",
			loading: false,
			saving: false,
			showSaveSuccess: false
		};
	},
	computed: {
		canSave() {
			return !this.loading && !this.saving && this.email;
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/settings.php/notification").done(result => {
				this.email = result.EmailAddress;
				this.notifymsg = result.EmailNewMessage;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		Save() {
			this.saving = true;
			this.error = "";
			$.post("/api/settings.php/notification", { emailnewmessage: this.notifymsg }).done(() => {
				this.showSaveSuccess = true;
				setTimeout(() => {
					this.showSaveSuccess = false;
				}, 2000);
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form class=tabcontent id=notification @submit.prevent=Save>
			<p class=error v-if=error>{{error}}</p>
			<label>
				<span class=label>e-mail:</span>
				<span class=field><span>{{email || "not specified"}} (change this in the <a href=#contact>contact section</a>)</span></span>
			</label>
			<label v-if=email>
				<span class=label>messages:</span>
				<span class="field checkbox"><input type=checkbox v-model=notifymsg> notify me by e-mail when someone sends me a message</span>
			</label>
			<div class=submit>
				<button :disabled=!canSave :class="{working: saving}">save</button>
				<Transition name=fadeout><p class=success v-if=showSaveSuccess>saved successfully</p></Transition>
			</div>
			<p>
				another way to keep up-to-date with track7 is to follow
				<a href="https://twitter.com/track7feed">@track7feed</a> on twitter.
				this includes the information on the track7 front page; there is no
				feed for your messages so you will need to enable e-mail
				notifications or visit the site to know when someone has sent you a
				message.
			</p>
		</form>
	`
};

const LinkedAccounts = {
	data() {
		return {
			authProviders: ["google", "twitter", "github", "deviantart", "steam", "twitch"],
			hasPassword: false,
			passwordUsesOldEncryption: false,
			accounts: [],
			loading: false,
			error: ""
		};
	},
	computed: {
		loginCount() {
			return this.accounts.length + this.hasPassword;
		},
		canRemove() {
			return this.loginCount > 1;
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/settings.php/logins").done(result => {
				this.hasPassword = result.HasPassword;
				this.passwordUsesOldEncryption = result.PasswordUsesOldEncryption;
				this.accounts = result.Accounts;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		},
		RemovePassword() {
			this.error = "";
			$.ajax({
				method: "DELETE",
				url: "/api/settings.php/password"
			}).done(() => {
				this.hasPassword = false;
				this.passwordUsesOldEncryption = false;
			}).fail(request => {
				this.error = request.responseText;
			});
		},
		RemoveLogin(site, id) {
			this.error = "";
			$.ajax({
				method: "DELETE",
				url: `/api/settings.php/login/${site}/${id}`
			}).done(() => {
				this.accounts = this.accounts.filter(account => account.Site != site || account.ID != id);
			}).fail(request => {
				this.error = request.responseText;
			});
		},
		AddLogin(auth) {
			$.get(`/api/user.php/auth/${auth}/`).done(redirectURL => {
				window.location = redirectURL;
			}).fail(request => {
				this.error = request.responseText;
			});
		}
	},
	template: /* html */ `
		<section class=tabcontent id=linkedaccounts>
			<p class=error v-if=error>{{error}}</p>

			<h2>username + password</h2>
			<p v-if=hasPassword>you have a password stored with track7 and can sign in using your username (${currentUser?.username}) and password.</p>
			<p class=securitynote v-if=passwordUsesOldEncryption>your password is not using the strongest encryption track7 offers.</p>
			<button v-if="hasPassword && canRemove" @click.prevent=RemovePassword>remove password</button>
			<p v-if=!hasPassword>you do not have a password stored with track7.</p>

			<h2>linked accounts</h2>
			<div v-for="account in accounts" key="account.Site + '/' + account.ID" class=linkedaccount :class=account.Site>
				<a v-if=account.URL :href=account.URL :title="'view the ' + account.Name + ' profile on ' + account.Site"><img :src=account.Avatar></a>
				<img v-if=!account.URL :src=account.Avatar :title="account.Name + ' on ' + account.Site">
				<div class=actions>
					<a v-if=canRemove class=unlink href="#removeaccount" @click.prevent="RemoveLogin(account.Site, account.ID)" title="unlink this account so it can no longer be used to sign in to track7"></a>
				</div>
			</div>

			<h2>authorize another account</h2>
			<p>
				you currently have {{loginCount}} {{loginCount == 1 ? "way" : "ways"}}
				to sign in to track7, but you can always add another. choose an
				account provider below to sign in and add it as a track7 sign in.
			</p>
			<div id=authchoices>
				<a v-for="auth in authProviders" key=auth :href="'/api/user.php/auth/' + auth + '/'" :class=auth :title="'link your ' + auth + ' account for sign in'" @click.prevent=AddLogin(auth)></a>
			</div>
		</section>
	`
};

const VisibilitySelector = {
	props: [
		"value",
		"includeNoneOption"
	],
	emits: [
		"changed"
	],
	data() {
		return {
			localValue: this.value
		};
	},
	watch: {
		value(val) {
			if(this.localValue != val)
				this.localValue = val;
		},
	},
	methods: {
		Toggle() {
			popup.toggle($(this.$refs.droplist));
		},
		Select(newValue) {
			this.localValue = newValue;
			this.$emit("changed", newValue);
			popup.hide();
		}
	},
	template: /* html */ `
		<a class="visibility droptrigger" :data-value=localValue :title="'shown to ' + localValue" :href="'#visibility-' + localValue" @click.prevent.stop=Toggle></a>
		<span class=droplist ref=droplist>
			<a v-if=includeNoneOption class=visibility data-value=none @click.prevent="Select('none')">nobody</a>
			<a class=visibility data-value=friends @click.prevent="Select('friends')">my track7 friends</a>
			<a class=visibility data-value=users @click.prevent="Select('users')">signed-in users</a>
			<a class=visibility data-value=all @click.prevent="Select('all')">everyone</a>
		</span>
	`
};

createApp({
	name: "Settings",
	data() {
		return {
			tabs: [
				{ id: "profile", name: "profile", description: "name and picture", component: "Profile" },
				{ id: "timezone", name: "time zone", description: "configure times to display for where you are", component: "TimeZone" },
				{ id: "contact", name: "contact", description: "e-mail and profiles on other sites", component: "Contact" },
				{ id: "notification", name: "notification", description: "choose when track7 should notify you", component: "Notification" },
				{ id: "linkedaccounts", name: "logins", description: "manage which accounts you use to sign in to track7", component: "LinkedAccounts" }
			],
			activeTab: null
		};
	},
	created() {
		this.ParseHash();
		$(window).on("hashchange", this.ParseHash);
	},
	methods: {
		ParseHash() {
			if(location.hash.length) {
				const hashTab = this.tabs.find(tab => "#" + tab.id == location.hash);
				if(hashTab) {
					this.activeTab = hashTab;
					return;
				}
			}
			if(this.activeTab)
				return;
			this.activeTab = this.tabs[0];
			if(history.replaceState)
				history.replaceState(null, null, "#" + this.activeTab.id);
			else
				location.hash = "#" + this.activeTab.id;
		}
	},
	template: /* html */ `
		<nav class=tabs>
			<a v-for="tab in tabs" :href="'#' + tab.id" :title=tab.description :class="{ selected: activeTab == tab }" @click="activeTab = tab">{{tab.name}}</a>
		</nav>

		<component :is="activeTab.component"></component>
	`
}).component("Profile", Profile)
	.component("TimeZone", TimeZone)
	.component("Contact", Contact)
	.component("Notification", Notification)
	.component("LinkedAccounts", LinkedAccounts)
	.component("VisibilitySelector", VisibilitySelector)
	.component("ValidatingField", ValidatingField)
	.mount(".tabbed");
