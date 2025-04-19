import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";
import { TagsField, FindAddedTags } from "tag";

createApp({
	name: "EditEntry",
	data() {
		return {
			entry: {},
			invalidFields: [],
			saving: false
		};
	},
	computed: {
		defaultUrl: function() {
			return NameToUrl(this.entry.Title);
		},
		effectiveUrl: function() {
			return this.entry.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.entry.Title && this.invalidFields.length <= 0 && this.entry.Markdown;
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
				autosize(this.$refs.markdownField);
			});
		},
		Load() {
			$.get("/api/blog.php/edit/" + this.id).done(entry => {
				this.entry = entry;
				this.originalTags = this.entry.Tags;
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
			this.entry[fieldName] = newValue;
		},
		TagsChanged(value) {
			this.entry.Tags = value;
		},
		Save() {
			this.saving = true;
			const url = "/api/blog.php/save/" + this.id;
			const data = {
				id: this.effectiveUrl,
				title: this.entry.Title,
				markdown: this.entry.Markdown,
				addtags: FindAddedTags(this.entry.Tags, this.originalTags),
				deltags: FindAddedTags(this.originalTags, this.entry.Tags)
			};

			$.post(url, data).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form @submit.prevent=Save>
			<label>
				<span class=label>title:</span>
				<span class=field><input name=title maxlength=128 required v-model=entry.Title></span>
			</label>
			<label>
				<span class=label>url:</span>
				<ValidatingField :value=entry.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/blog.php/idAvailable/' + this.id"
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label class=multiline>
				<span class=label>entry:</span>
				<span class=field><textarea id=content required rows="" cols="" v-model=entry.Markdown ref=markdownField></textarea></span>
			</label>
			<label>
				<span class=label>tags:</span>
				<TagsField :tags=entry.Tags @changed=TagsChanged></TagsField>
			</label>
			<button :disabled=!canSave :class="{working: saving}">save</button>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.component("TagsField", TagsField)
	.mount("#editentry");;
