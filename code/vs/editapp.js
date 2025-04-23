import "jquery";
import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";

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
		defaultUrl: function() {
			return NameToUrl(this.app.Name);
		},
		effectiveUrl: function() {
			return this.app.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.app.Name && this.invalidFields.size <= 0 && this.app.Markdown && (this.id || this.icon);
		}
	},
	created() {
		const queryString = new URLSearchParams(window.location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else {
			this.Autosize();
		}
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.markdownField);
			});
		},
		Load() {
			$.get("/api/application.php/edit/" + this.id).done(entry => {
				this.app = entry;
				this.Autosize();
			}).fail(request => {
				alert(request.responseText);
			});
		},
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
		Save() {
			this.saving = true;
			const data = new FormData(this.$refs.appForm);
			data.append("id", this.effectiveUrl);
			data.append("name", this.app.Name);
			data.append("markdown", this.app.Markdown);
			if(this.app.GitHub)
				data.append("github", this.app.GitHub);
			if(this.app.Wiki)
				data.append("wiki", this.app.Wiki);

			$.post({ url: "/api/application.php/save/" + this.id, data: data, contentType: false, processData: false }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
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
				<ValidatingField :value=app.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/application.php/idAvailable/' + this.id"
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
