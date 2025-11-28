import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { ValidatingField } from "validate";
import DateApi from "/api/date.js";
import UpdateApi from "/api/update.js";

createApp({
	name: "EditUpdate",
	data() {
		return {
			markdown: "",
			date: "",
			invalidFields: new Set(),
			saving: false,
			error: ""
		};
	},
	computed: {
		canSave() {
			return !this.saving && this.markdown && this.invalidFields.size <= 0;
		}
	},
	async created() {
		await nextTick();
		autosize(this.$refs.markdown);
	},
	methods: {
		validateDate: DateApi.validatePast,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this[fieldName] = newValue;
		},
		async Save() {
			this.saving = true;
			try {
				const result = await UpdateApi.add(this.markdown, this.date);
				location.href = result;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.saving = false;
			}
		},
	},
	template: /* html */ `
		<form method=post @submit.prevent=Save>
			<label class=multiline>
				<span class=label>update:</span>
				<span class=field><textarea v-model=markdown required rows="" cols="" ref=markdown></textarea></span>
			</label>
			<label>
				<span class=label>date:</span>
				<ValidatingField :value=date :validate=validateDate
					msgChecking="validating date / time..." msgValid="valid date / time"
					msgBlank="will use current date / time" :isBlankValid=true
					@validated="(isValid, newValue) => OnValidated('date', isValid, newValue)"
				></ValidatingField>
			</label>
			<button id=save :disabled=!canSave :class="{working: saving}">save</button>
			<p class=error v-if=error>{{error}}</p>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.mount("#editupdate");
