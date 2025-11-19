import "jquery";
import { createApp } from "vue";
import { ValidatingField } from "validate";

if($("#newuser").length)
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
			canSave: function() {
				return !this.saving && this.invalidFields.size <= 0;
			}
		},
		created() {
			this.Load();
		},
		methods: {
			Load() {
				this.loading = true;
				$.get("/api/user.php/registration").done(result => {
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
			Register() {
				if(this.canSave) {
					this.saving = true;
					this.error = "";
					$.post("/api/user.php/register", {
						csrf: this.csrf,
						username: this.username,
						displayname: this.displayname,
						email: this.email,
						website: this.website,
						linkprofile: this.linkprofile,
						useavatar: this.useavatar
					}).done(result => {
						location = result;
					}).fail(request => {
						this.error = request.responseText;
					}).always(() => {
						this.saving = false;
					});
				}
			}
		},
		template: /* html */ `
			<p class=loading v-if=loading>loading...</p>
			<form @submit.prevent=Register>
				<label>
					<span class=label>username:</span>
					<ValidatingField :value=username validateUrl="/api/user.php/idAvailable"
						msgChecking="validating username..." msgValid="username available" msgBlank="username required"
						inputAttributes="{maxlength: 32, required: true, pattern: '[a-zA-Z0-9_\-]+'}"
						@validated="(isValid, newValue) => OnValidated('username', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>display name:</span>
					<ValidatingField :value=displayname :default=username validateUrl="/api/user.php/nameAvailable" :isBlankValid=true
						msgChecking="validating display name..." msgValid="display name available" msgBlank="username will be used for display"
						inputAttributes="{maxlength: 32}"
						@validated="(isValid, newValue) => OnValidated('displayname', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>e-mail:</span>
					<ValidatingField :value=email validateUrl="/api/contact.php/validate/email" :isBlankValid=true
						msgChecking="validating e-mail address..." msgValid="e-mail address available" msgBlank="e-mail address will be left blank"
						inputAttributes="{maxlength: 64}"
						@validated="(isValid, newValue) => OnValidated('email', isValid, newValue)"
					></ValidatingField>
				</label>
				<label>
					<span class=label>website:</span>
					<ValidatingField :value=website validateUrl="/api/contact.php/validate/website" :isBlankValid=true
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
