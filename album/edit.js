import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import { TagsField } from "tag";
import DateApi from "/api/date.js";
import PhotoApi from "/api/photo.js";

createApp({
	name: "EditPhoto",
	data() {
		return {
			photo: {},
			image: null,
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.photo.Title);
		},
		effectiveUrl() {
			return this.photo.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.photo.Title && this.invalidFields.size <= 0 && this.photo.StoryMarkdown && (this.image || this.id);
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
			await nextTick();
			autosize(this.$refs.storyField);
		},
		async Load() {
			try {
				const photo = await PhotoApi.edit(this.id);
				if(!photo.StoryMarkdown)
					photo.StoryMarkdown = photo.Story;
				delete photo.Story;
				this.photo = photo;
				this.originalTags = this.photo.Tags;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: PhotoApi.idAvailable,
		validateTaken: DateApi.validatePast,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.photo[fieldName] = newValue;
		},
		PreviewPhoto(event) {
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
			this.photo.Tags = value;
		},
		async Save() {
			this.saving = true;
			const data = new FormData(this.$refs.photoForm);

			try {
				const result = await PhotoApi.save(this.id, data, this.effectiveUrl, this.photo.TakenFormatted, this.photo.Tags, this.originalTags);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
		}
	},
	template: /* html */ `
		<form @submit.prevent=Save ref=photoForm>
			<label>
				<span class=label>caption:</span>
				<span class=field><input maxlength=32 required name=title v-model=photo.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=photo.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label title="youtube video id if this photo is a video (unique part of the video url)">
				<span class=label>youtube:</span>
				<span class=field><input maxlength=32 name=youtube v-model=photo.Youtube></span>
			</label>
			<label title="upload the photo, or a thumbnail for a video">
				<span class=label>photo:</span>
				<span class=field>
					<input type=file name=image accept="image/jpeg, image/jpg" @change=PreviewPhoto :class="{hidden: image}">
					<img class="photo preview" v-if=image :src=image>
				</span>
			</label>
			<label class=multiline>
				<span class=label>story:</span>
				<span class=field><textarea ref=storyField name=story v-model=photo.StoryMarkdown></textarea></span>
			</label>
			<label>
				<span class=label>taken:</span>
				<ValidatingField :value=photo.TakenFormatted :validate=validateTaken
				msgChecking="validating date / time..." msgValid="valid date / time"
					msgBlank="will attempt to look up from photo exif data" :isBlankValid=true
					@validated="(isValid, newValue) => OnValidated('TakenFormatted', isValid, newValue)"
				></ValidatingField>
			</label>
			<label>
				<span class=label>year:</span>
				<span class=field><input pattern="[0-9]{4}" maxlength=4 name=year v-model=photo.Year></span>
			</label>
			<label>
				<span class=label>tags:</span>
				<TagsField :tags=photo.Tags @changed=TagsChanged></TagsField>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
			<p v-if=id><img class=photo :src="photo.ID ? 'photos/' + photo.ID + '.jpeg' : ''"></p>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.component("TagsField", TagsField)
	.mount("#editphoto");
