import "jquery";
import { createApp } from "vue";
import { ValidatingField } from "validate";
import autosize from "autosize";

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
	created() {
		this.$nextTick(() => {
			autosize(this.$refs.markdown);
		});
	},
	methods: {
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this[fieldName] = newValue;
		},
		Save() {
			this.saving = true;
			$.post("/api/update.php/add", { markdown: this.markdown, posted: this.date }).done(result => {
				window.location.href = result;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.saving = false;
			});
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
				<ValidatingField :value=date validateUrl="/api/date.php/validatePast/"
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
