import { createApp } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import LegoApi from "/api/lego.js";

createApp({
	name: "EditLego",
	data() {
		return {
			lego: {},
			image: null,
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.lego.Title);
		},
		effectiveUrl() {
			return this.lego.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.lego.Title && this.invalidFields.size <= 0 && this.lego.Description && (this.id || this.image && this.$refs.ldrawField.files.length && this.$refs.pdfField.files.length);
		}
	},
	created() {
		const queryString = new URLSearchParams(location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else
			this.Autosize();
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.descriptionField);
			});
		},
		async Load() {
			try {
				const lego = await LegoApi.edit(this.id);
				this.lego = lego;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: LegoApi.idAvailable,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.lego[fieldName] = newValue;
		},
		PreviewImage(event) {
			const file = event.target.files[0];
			if(file) {
				const reader = new FileReader();
				reader.onloadend = () => {
					this.image = reader.result;
				};
				reader.readAsDataURL(file);
			} else
				this.image = null;
		},
		async Save() {
			this.saving = true;
			const data = new FormData(this.$refs.legoForm);
			try {
				const result = await LegoApi.save(
					this.id,
					data,
					this.effectiveUrl,
					this.lego.Title,
					this.lego.Pieces,
					this.lego.Description
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
		<form @submit.prevent=Save ref=legoForm>
			<label>
				<span class=label>title:</span>
				<span class=field><input maxlength=32 required v-model=lego.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=lego.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label title="upload a 3d rendered image" :class="{multiline: image}">
				<span class=label>image:</span>
				<span class=field>
					<input type=file name=image accept=".png, image/png" @change=PreviewImage :class="{hidden: image}">
					<img class="art preview" v-if=image :src=image>
				</span>
			</label>
			<label title="upload ldraw data file">
				<span class=label>ldraw file:</span>
				<span class=field>
					<input type=file name=ldraw ref=ldrawField accept=".ldr">
				</span>
			</label>
			<label title="upload step-by-step instructionss (pdf)">
				<span class=label>instructions:</span>
				<span class=field>
					<input type=file name=instructions ref=pdfField accept="application/pdf">
				</span>
			</label>
			<label title="number of pieces in this model">
				<span class=label>pieces:</span>
				<span class=field>
					<input type=number min=3 max=9999 maxlength=4 step=1 v-model=lego.Pieces>
				</span>
			</label>
			<label class=multiline>
				<span class=label>description:</span>
				<span class=field><textarea v-model=lego.Description ref=descriptionField></textarea></span>
			</label>
			<button id=save :disabled=!canSave :class="{working: saving}">save</button>
			<p v-if=id><img class="art preview" :src="id ? 'data/' + id + '.png' : ''"></p>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.mount("#editlego");
