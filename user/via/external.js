import { createApp } from "vue";
import { ValidatingField } from "validate";
import ContactApi from "/api/contact.js";
import UserApi from "/api/user.js";

if(document.querySelector("#newuser"))
	createApp({
		name: "NewUser",
		data() {
			return {
				username: "",
				displayname: "",
				email: "",
				website: "",
				avatar: "",
				profile: "",
				linkprofile: false,
				useavatar: false,
				invalidFields: new Set(),
				error: "",
				loading: false,
				saving: false
			};
		},
		computed: {
			canSave() {
				return !this.saving && this.invalidFields.size <= 0;
			}
		},
		created() {
			this.Load();
		},
		methods: {
			async Load() {
				this.loading = true;
				try {
					const result = UserApi.registration();
					this.provider = result.Provider;
					this.username = result.Username;
					this.displayname = result.DisplayName;
					this.email = result.Email;
					this.website = result.Website;
					this.avatar = result.Avatar;
					this.useavatar = !!result.Avatar;
					this.profile = result.ProfileURL;
					this.linkprofile = !!result.ProfileURL;
					this.csrf = result.CSRF;
				} catch(error) {
					this.error = error.message;
				} finally {
					this.loading = false;
				}
			},
			validateUsername: UserApi.idAvailable,
			validateDisplayname: UserApi.nameAvailable,
			validateEmail: ContactApi.validateEmail,
			validateWebsite: ContactApi.validateWebsite,
			OnValidated(fieldName, isValid, newValue) {
				if(isValid)
					this.invalidFields.delete(fieldName);
				else
					this.invalidFields.add(fieldName);
				this[fieldName] = newValue;
			},
			async Register() {
				if(this.canSave) {
					this.saving = true;
					this.error = "";
					try {
						const result = UserApi.register(
							this.csrf,
							this.username,
							this.displayname,
							this.email,
							this.website,
							this.linkprofile,
							this.useavatar
						);
						location = result;
					} catch(error) {
						this.error = error.message;
					} finally {
						this.saving = false;
					}
				}
			}
		},
		template: /* html */ `
			<p class=loading v-if=loading>loading...</p>
			<form @submit.prevent=Register>
				<label>
					<span class=label>username:</span>
					<ValidatingField :value=username :validate=validateUsername
						msgChecking="validating username..." msgValid="username available" msgBlank="username required"
						inputAttributes="{maxlength: 32, required: true, pattern: '[a-zA-Z0-9_\-]+'}"
						@validated="(isValid, newValue) => OnValidated('username', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>display name:</span>
					<ValidatingField :value=displayname :default=username :validate=validateDisplayname :isBlankValid=true
						msgChecking="validating display name..." msgValid="display name available" msgBlank="username will be used for display"
						inputAttributes="{maxlength: 32}"
						@validated="(isValid, newValue) => OnValidated('displayname', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>e-mail:</span>
					<ValidatingField :value=email :validate=validateEmail :isBlankValid=true
						msgChecking="validating e-mail address..." msgValid="e-mail address available" msgBlank="e-mail address will be left blank"
						inputAttributes="{maxlength: 64}"
						@validated="(isValid, newValue) => OnValidated('email', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>website:</span>
					<ValidatingField :value=website :validate=validateWebsite :isBlankValid=true
						msgChecking="validating website url..." msgValid="url exists" msgBlank="no website listed"
						inputAttributes="{maxlength: 64}"
						@validated="(isValid, newValue) => OnValidated('website', isValid, newValue)"
					></ValidatingField>
				</label>
				<label v-if=profile>
					<span class=checkbox><input type=checkbox v-model=linkprofile> link <a :href=profile>this profile</a> as your {{provider}} profile</span>
				</label>
				<label v-if=avatar>
					<span class=checkbox><input type=checkbox v-model=useavatar> use this profile picture: <img class=avatar :src=avatar></span>
				</label>
				<button :disabled=!canSave>complete sign in</button>
			</form>
			<p class=error v-if=error>{{error}}</p>
		`
	}).component("ValidatingField", ValidatingField)
		.mount("#newuser");
