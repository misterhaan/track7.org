import { createApp } from "vue";
import autosize from "autosize";
import { ValidatingField } from "validate";
import DateApi from "/api/date.js";
import ReleaseApi from "/api/release.js";

createApp({
	name: "AddRelease",
	data() {
		return {
			release: {},
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		canSave() {
			return !this.saving && this.release.Version && this.release.BinURL && this.invalidFields.size <= 0;
		}
	},
	created() {
		const queryString = new URLSearchParams(location.search);
		this.app = queryString.get("app") || "";
		this.Autosize();
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.markdownField);
			});
		},
		validateVersion: ReleaseApi.versionAvailable,
		validateInstant: DateApi.validatePast,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.release[fieldName] = newValue;
		},
		async Save() {
			this.saving = true;
			try {
				const result = await ReleaseApi.add(
					this.app,
					this.release.Version,
					this.release.Instant,
					this.release.Language,
					this.release.DotNet,
					this.release.VisualStudio,
					this.release.Changelog,
					this.release.BinURL,
					this.release.Bin32URL,
					this.release.SrcURL
				);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
		}
	},
	template: /* html */ `
		<form method=post enctype="" @submit.prevent=Save ref=relForm>
			<label>
				<span class=label>version:</span>
				<ValidatingField :value=release.Version :validate=validateVersion
					msgChecking="checking if version is already released..." msgValid="can release this version" msgBlank="version is required"
					inputAttributes="{maxlength: 10, pattern: '[0-9]+(\.[0-9]+){0,2}', required: true}"
					@validated="(isValid, newValue) => OnValidated('Version', isValid, newValue)"
				></ValidatingField>
			</label>
			<label>
				<span class=label>date:</span>
				<ValidatingField :value=release.Instant :validate=validateInstant
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
