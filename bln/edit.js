import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import { TagsField } from "tag";
import BlogApi from "/api/blog.js";

createApp({
	name: "EditEntry",
	data() {
		return {
			entry: {},
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.entry.Title);
		},
		effectiveUrl() {
			return this.entry.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.entry.Title && this.invalidFields.size <= 0 && this.entry.Markdown;
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
			autosize(this.$refs.markdownField);
		},
		async Load() {
			try {
				const entry = await BlogApi.edit(this.id);
				this.entry = entry;
				this.originalTags = this.entry.Tags;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: BlogApi.idAvailable,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.entry[fieldName] = newValue;
		},
		TagsChanged(value) {
			this.entry.Tags = value;
		},
		async Save() {
			this.saving = true;
			try {
				const result = await BlogApi.save(this.id, this.effectiveUrl, this.entry.Title, this.entry.Markdown, this.entry.Tags, this.originalTags);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
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
				<ValidatingField :value=entry.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
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
