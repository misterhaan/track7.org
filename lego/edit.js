import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";

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
		defaultUrl: function() {
			return NameToUrl(this.lego.Title);
		},
		effectiveUrl: function() {
			return this.lego.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.lego.Title && this.invalidFields.size <= 0 && this.lego.Description && (this.id || this.image && this.$refs.ldrawField.files.length && this.$refs.pdfField.files.length);
		}
	},
	created() {
		const queryString = new URLSearchParams(window.location.search);
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
		Load() {
			$.get("/api/lego.php/edit/" + this.id).done(lego => {
				this.lego = lego;
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
		Save() {
			this.saving = true;
			const data = new FormData(this.$refs.legoForm);
			data.append("id", this.effectiveUrl);
			data.append("title", this.lego.Title);
			data.append("pieces", this.lego.Pieces);
			data.append("description", this.lego.Description);

			$.post({ url: "/api/lego.php/save/" + this.id, data: data, contentType: false, processData: false }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
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
				<ValidatingField :value=lego.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/lego.php/idAvailable/' + this.id"
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
