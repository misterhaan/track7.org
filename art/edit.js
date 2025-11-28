import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import { TagsField } from "tag";
import ArtApi from "/api/art.js";

createApp({
	name: "EditArt",
	data() {
		return {
			art: {},
			image: null,
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.art.Title);
		},
		effectiveUrl() {
			return this.art.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.art.Title && this.invalidFields.size <= 0 && this.art.Description && (this.image || this.id);
		}
	},
	created() {
		const queryString = new URLSearchParams(location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else {
			this.originalTags = "";
			this.Autosize();
		}
	},
	methods: {
		async Autosize() {
			nextTick();
			autosize(this.$refs.descriptionField);
		},
		async Load() {
			try {
				const art = await ArtApi.edit(this.id);
				this.art = art;
				this.originalTags = this.art.Tags;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: ArtApi.idAvailable,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.art[fieldName] = newValue;
		},
		PreviewArt(event) {
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
		TagsChanged(value) {
			this.art.Tags = value;
		},
		async Save() {
			this.saving = true;
			const data = new FormData(this.$refs.artForm);
			try {
				const result = await ArtApi.save(this.id, data, this.effectiveUrl, this.art.Tags, this.originalTags);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
		}
	},
	template: /* html */ `
		<form @submit.prevent=Save ref=artForm>
			<label>
				<span class=label>title:</span>
				<span class=field><input name=title maxlength=32 required v-model=art.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=art.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label title="upload the art">
				<span class=label>art:</span>
				<span class=field>
					<input type=file name=image accept=".jpg, .jpeg, .png, image/jpeg, image/jpg, image/png" @change=PreviewArt :class="{hidden: image}">
					<img class="art preview" v-if=image :src=image>
				</span>
			</label>
			<label class=multiline>
				<span class=label>description:</span>
				<span class=field><textarea name=description v-model=art.Description ref=descriptionField></textarea></span>
			</label>
			<label>
				<span class=label>deviantart:</span>
				<span class=field>https://deviantart.com/art/<input name=deviation maxlength=64 v-model=art.Deviation></span>
			</label>
			<label>
				<span class=label>tags:</span>
				<TagsField :tags=art.Tags @changed=TagsChanged></TagsField>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
			<p v-if="id && art.Ext"><img class="art preview" :src="id ? 'img/' + id + '.' + art.Ext : ''"></p>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.component("TagsField", TagsField)
	.mount("#editart");
