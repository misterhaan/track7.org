import { createApp } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import DateApi from "/api/date.js";
import ScriptApi from "/api/script.js";

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
		defaultUrl() {
			return NameToUrl(this.script.Title);
		},
		effectiveUrl() {
			return this.script.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.script.Title && this.script.Description && (this.filelocation == "link" && this.script.Download || this.filelocation == "upload" && (this.id || this.$refs.fileUpload.files.length)) && this.invalidFields.size <= 0;
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
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.description);
				autosize(this.$refs.instructions);
			});
		},
		async Load() {
			try {
				const script = await ScriptApi.edit(this.id);
				this.script = script;
				if(script.Download)
					this.filelocation = "link";
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: ScriptApi.idAvailable,
		validateInstant: DateApi.validatePast,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.script[fieldName] = newValue;
		},
		async Save() {
			this.saving = true;
			const data = new FormData(this.$refs.scriptForm);
			try {
				const result = await ScriptApi.save(
					this.id,
					data,
					this.effectiveUrl,
					this.script.Title,
					this.script.Type,
					this.script.Description,
					this.script.Instructions,
					this.filelocation == "link" ? this.script.Download : null,
					this.script.GitHub,
					this.script.Wiki,
					this.script.FormattedInstant
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
		<form method=post enctype="" @submit.prevent=Save ref=scriptForm>
			<label>
				<span class=label>name:</span>
				<span class=field><input maxlength=32 required v-model=script.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=script.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
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
				<ValidatingField :value=script.FormattedInstant :validate=validateInstant
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
