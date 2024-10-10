import "jquery";
import { createApp } from 'vue';
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import { TagsField, FindAddedTags } from "tag";

createApp({
	name: "EditPhoto",
	data() {
		return {
			photo: {},
			image: null,
			invalidFields: [],
			saving: false
		};
	},
	computed: {
		defaultUrl: function() {
			return NameToUrl(this.photo.Title);
		},
		effectiveUrl: function() {
			return this.photo.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.photo.Title && this.invalidFields.length <= 0 && this.photo.StoryMarkdown && (this.image || this.id);
		}
	},
	created: function() {
		const queryString = new URLSearchParams(window.location.search);
		if(this.id = queryString.get("id") || "")
			this.Load();
		else
			this.originalTags = "";
		this.$nextTick(() => {
			autosize(this.$refs.storyField);
		});
	},
	methods: {
		Load() {
			$.get("/api/photo.php/edit/" + this.id).done(photo => {
				if(!photo.StoryMarkdown)
					photo.StoryMarkdown = photo.Story;
				delete photo.Story;
				this.photo = photo;
				this.originalTags = this.photo.Tags;
			}).fail(request => {
				alert(request.responseText);
			});
		},
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields = this.invalidFields.filter(field => field != fieldName);
			else
				this.invalidFields.push(fieldName);
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
		Save: function() {
			this.saving = true;
			const data = new FormData(this.$refs.photoForm);
			data.append("id", this.effectiveUrl);
			data.append("taken", this.photo.TakenFormatted);
			data.append("addtags", FindAddedTags(this.photo.Tags, this.originalTags));
			data.append("deltags", FindAddedTags(this.originalTags, this.photo.Tags));

			$.post({ url: "/api/photo.php/save/" + this.id, data: data, contentType: false, processData: false }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form v-on:submit.prevent=Save ref=photoForm>
			<label>
				<span class=label>caption:</span>
				<span class=field><input maxlength=32 required name=title v-model=photo.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=photo.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/photo.php/idAvailable/' + this.id + '='"
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+'}"
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
				<ValidatingField :value=photo.TakenFormatted validateUrl="/api/date.php/validatePast/"
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
