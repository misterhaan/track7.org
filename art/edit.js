import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";
import { TagsField, FindAddedTags } from "tag";

createApp({
	data() {
		return {
			art: {},
			image: null,
			invalidFields: [],
			saving: false
		};
	},
	computed: {
		defaultUrl: function() {
			return NameToUrl(this.art.Title);
		},
		effectiveUrl: function() {
			return this.art.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.art.Title && this.invalidFields.length <= 0 && this.art.Description && (this.image || this.id);
		}
	},
	created() {
		const queryString = new URLSearchParams(window.location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else {
			this.originalTags = "";
			this.Autosize();
		}
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.descriptionField);
			});
		},
		Load() {
			$.get("/api/art.php/edit/" + this.id).done(art => {
				this.art = art;
				this.originalTags = this.art.Tags;
				this.Autosize();
			}).fail(request => {
				alert(request.responseText);
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields = this.invalidFields.filter(field => field != fieldName);
			else
				this.invalidFields.push(fieldName);
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
		Save() {
			this.saving = true;
			const data = new FormData(this.$refs.artForm);
			data.append("id", this.effectiveUrl);
			data.append("addtags", FindAddedTags(this.art.Tags, this.originalTags));
			data.append("deltags", FindAddedTags(this.originalTags, this.art.Tags));

			$.post({ url: "/api/art.php/save/" + this.id, data: data, contentType: false, processData: false }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
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
				<ValidatingField :value=art.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/art.php/idAvailable/' + this.id + '='"
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+'}"
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
