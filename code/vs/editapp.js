import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import ApplicationApi from "/api/application.js";

createApp({
	name: "EditApp",
	data() {
		return {
			app: {},
			icon: null,
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.app.Name);
		},
		effectiveUrl() {
			return this.app.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.app.Name && this.invalidFields.size <= 0 && this.app.Markdown && (this.id || this.icon);
		}
	},
	created() {
		const queryString = new URLSearchParams(location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else {
			this.Autosize();
		}
	},
	methods: {
		async Autosize() {
			await nextTick();
			autosize(this.$refs.markdownField);
		},
		async Load() {
			try {
				const entry = await ApplicationApi.edit(this.id);
				this.app = entry;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: ApplicationApi.idAvailable,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.app[fieldName] = newValue;
		},
		PreviewIcon(event) {
			const file = event.target.files[0];
			if(file) {
				const reader = new FileReader();
				reader.onloadend = () => {
					this.icon = reader.result;
				};
				reader.readAsDataURL(file);
			} else
				this.icon = null;
		},
		async Save() {
			this.saving = true;
			const data = new FormData(this.$refs.appForm);
			try {
				const result = await ApplicationApi.save(
					this.id,
					data,
					this.effectiveUrl,
					this.app.Name,
					this.app.Markdown,
					this.app.GitHub,
					this.app.Wiki
				);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
		},
	},
	template: /* html */ `
		<form method=post enctype="" @submit.prevent=Save ref=appForm>
			<label>
				<span class=label>name:</span>
				<span class=field><input maxlength=32 required v-model=app.Name></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=app.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label class=multiline>
				<span class=label>description:</span>
				<span class=field><textarea required rows="" cols="" v-model=app.Markdown ref=markdownField></textarea></span>
			</label>
			<label>
				<span class=label>icon:</span>
				<span class=field><input type=file name=icon accept=".png, image/png" @change=PreviewIcon :class="{hidden: icon}" :required=!this.id><img class="icon preview" :src=icon v-if=icon></span>
			</label>
			<label>
				<span class=label>github:</span>
				<span class=field>https://github.com/misterhaan/<input maxlength=16 v-model=app.GitHub></span>
			</label>
			<label>
				<span class=label>auwiki:</span>
				<span class=field>https://wiki.track7.org/<input maxlength=32 v-model=app.Wiki></span>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.mount("#editapp");;
