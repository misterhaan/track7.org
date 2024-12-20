import { createApp } from 'vue';
import { ValidatingField } from "validate";
import autosize from "autosize";

createApp({
	name: "AddRelease",
	data() {
		return {
			release: {},
			invalidFields: [],
			saving: false
		};
	},
	computed: {
		canSave: function() {
			return !this.saving && this.release.Version && this.release.BinURL && this.invalidFields.length <= 0;
		}
	},
	created() {
		const queryString = new URLSearchParams(window.location.search);
		this.app = queryString.get("app") || "";
		this.Autosize();
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.markdownField);
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields = this.invalidFields.filter(field => field != fieldName);
			else
				this.invalidFields.push(fieldName);
			this.release[fieldName] = newValue;
		},
		Save() {
			this.saving = true;
			const data = {
				version: this.release.Version,
				instant: this.release.Instant,
				language: this.release.Language,
				dotnet: this.release.DotNet,
				visualstudio: this.release.VisualStudio,
				changelog: this.release.Changelog,
				binurl: this.release.BinURL,
				bin32url: this.release.Bin32URL,
				srcurl: this.release.SrcURL
			};
			$.post("/api/release.php/add/" + this.app, data).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form method=post enctype="" @submit.prevent=Save ref=relForm>
			<label>
				<span class=label>version:</span>
				<ValidatingField :value=release.Version :validateUrl="'/api/release.php/versionAvailable/' + this.app + '/'"
					msgChecking="checking if version is already released..." msgValid="can release this version" msgBlank="version is required"
					inputAttributes="{maxlength: 10, pattern: '[0-9]+(\.[0-9]+){0,2}'}"
					@validated="(isValid, newValue) => OnValidated('Version', isValid, newValue)"
				></ValidatingField>
			</label>
			<label>
				<span class=label>date:</span>
				<ValidatingField :value=release.Instant validateUrl="/api/date.php/validatePast/"
				msgChecking="validating date / time..." msgValid="valid date / time"
					msgBlank="will use current date / time" :isBlankValid=true
					@validated="(isValid, newValue) => OnValidated('Instant', isValid, newValue)"
				></ValidatingField>
			</label>
			<label>
				<span class=label>language:</span>
				<span class=field><select required v-model=release.Language>
					<option>c#</option>
					<option>vb</option>
				</select></span>
			</label>
			<label>
				<span class=label>.net:</span>
				<span class=field><input type=number v-model=release.DotNet></span>
			</label>
			<label>
				<span class=label>studio:</span>
				<span class=field><input type=number v-model=release.VisualStudio required></span>
			</label>
			<label class=multiline>
				<span class=label>changes:</span>
				<span class=field><textarea v-model=release.Changelog ref=markdownField></textarea></span>
			</label>
			<label>
				<span class=label>bin url:</span>
				<span class=field><input v-model=release.BinURL required maxlength=128></span>
			</label>
			<label>
				<span class=label>bin32 url:</span>
				<span class=field><input v-model=release.Bin32URL maxlength=128></span>
			</label>
			<label>
				<span class=label>src url:</span>
				<span class=field><input v-model=release.SrcURL maxlength=128></span>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.mount("#addrel");;
