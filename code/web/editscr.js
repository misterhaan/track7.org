import "jquery";
import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";

createApp({
	name: "EditScript",
	data() {
		return {
			script: {},
			icon: null,
			filelocation: "",
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl: function() {
			return NameToUrl(this.script.Title);
		},
		effectiveUrl: function() {
			return this.script.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.script.Title && this.script.Description && (this.filelocation == "link" && this.script.Download || this.filelocation == "upload" && (this.id || this.$refs.fileUpload.files.length)) && this.invalidFields.size <= 0;
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
				autosize(this.$refs.description);
				autosize(this.$refs.instructions);
			});
		},
		Load() {
			$.get("/api/script.php/edit/" + this.id).done(script => {
				this.script = script;
				if(script.Download)
					this.filelocation = "link";
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
			this.script[fieldName] = newValue;
		},
		Save() {
			this.saving = true;
			const data = new FormData(this.$refs.scriptForm);
			data.append("id", this.effectiveUrl);
			data.append("title", this.script.Title);
			data.append("type", this.script.Type);
			data.append("description", this.script.Description);
			data.append("instructions", this.script.Instructions);
			if(this.filelocation == "link")
				data.append("download", this.script.Download);
			if(this.script.GitHub)
				data.append("github", this.script.GitHub);
			if(this.script.Wiki)
				data.append("wiki", this.script.Wiki);
			if(this.script.FormattedInstant)
				data.append("instant", this.script.FormattedInstant);

			$.post({ url: "/api/script.php/save/" + this.id, data: data, contentType: false, processData: false }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		},
	},
	template: /* html */ `
		<form method=post enctype="" @submit.prevent=Save ref=scriptForm>
			<label>
				<span class=label>name:</span>
				<span class=field><input maxlength=32 required v-model=script.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=script.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/script.php/idAvailable/' + this.id"
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-\\._]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label>
				<span class=label>type:</span>
				<span class=field><select v-model=script.Type required>
					<option disabled selected value="">(choose a script type)</option>
					<option>website</option>
					<option>web application</option>
					<option>userscript</option>
					<option>snippet</option>
					<option>api</option>
				</select></span>
			</label>
			<label class=multiline>
				<span class=label>description:</span>
				<span class=field><textarea required rows="" cols="" v-model=script.Description ref=description></textarea></span>
			</label>
			<label class=multiline>
				<span class=label>instructions:</span>
				<span class=field><textarea rows="" cols="" v-model=script.Instructions ref=instructions></textarea></span>
			</label>
			<fieldset class=selectafield>
				<div>
					<label class=label><input type=radio value=upload v-model=filelocation>upload:</label>
					<label class=field><input :disabled="filelocation != 'upload'" name=upload type=file ref=fileUpload></label>
				</div>
				<div>
					<label class=label><input type=radio value=link v-model=filelocation>link:</label>
					<label class=field><input :required="filelocation == 'link'" :disabled="filelocation != 'link'" type=url v-model=script.Download maxlength=64></label>
				</div>
			</fieldset>
			<label>
				<span class=label>github:</span>
				<span class=field>https://github.com/misterhaan/<input maxlength=16 v-model=script.GitHub></span>
			</label>
			<label>
				<span class=label>auwiki:</span>
				<span class=field>https://wiki.track7.org/<input maxlength=32 v-model=script.Wiki></span>
			</label>
			<label>
				<span class=label>date:</span>
				<ValidatingField :value=script.FormattedInstant validateUrl="/api/date.php/validatePast"
				msgChecking="validating date / time..." msgValid="valid date / time"
					msgBlank="will use current date / time" :isBlankValid=true
					@validated="(isValid, newValue) => OnValidated('FormattedInstant', isValid, newValue)"
				></ValidatingField>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.mount("#editscr");;
