import { createApp } from "vue";
import autosize from "autosize";
import { NameToUrl, ValidatingField } from "validate";
import { TagsField } from "tag";
import GuideApi from "/api/guide.js";

createApp({
	name: "EditGuide",
	data() {
		return {
			guide: {
				Title: "",
				ID: "",
				Summary: "",
				Level: "intermediate",
				Tags: "",
				Chapters: [{
					Title: "",
					Markdown: ""
				}]
			},
			correctionsOnly: false,
			invalidFields: new Set(),
			saving: false
		};
	},
	computed: {
		defaultUrl() {
			return NameToUrl(this.guide.Title);
		},
		effectiveUrl() {
			return this.guide.ID || this.defaultUrl;
		},
		canSave() {
			return !this.saving && this.guide.Title && this.guide.Summary && this.invalidFields.size <= 0 && this.guide.Chapters.every(p => p.Title && p.Markdown);
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
		Autosize(update) {
			this.$nextTick(() => {
				if(update)
					autosize.update(document.querySelectorAll("textarea"));
				else
					autosize(document.querySelectorAll("textarea"));
			});
		},
		async Load() {
			try {
				const guide = await GuideApi.edit(this.id);
				this.guide = guide;
				this.originalTags = this.guide.Tags;
				this.Autosize();
			} catch(error) {
				alert(error.message);
			}
		},
		validateId: GuideApi.idAvailable,
		OnValidated(fieldName, isValid, newValue) {
			if(isValid)
				this.invalidFields.delete(fieldName);
			else
				this.invalidFields.add(fieldName);
			this.guide[fieldName] = newValue;
		},
		TagsChanged(value) {
			this.guide.Tags = value;
		},
		MoveChapterUp(chapter) {
			const index = this.guide.Chapters.indexOf(chapter);
			if(index > 0) {
				this.guide.Chapters.splice(index, 1);
				this.guide.Chapters.splice(index - 1, 0, chapter);
			}
			this.Autosize(true);
		},
		MoveChapterDown(chapter) {
			const index = this.guide.Chapters.indexOf(chapter);
			if(index >= 0 && index < this.guide.Chapters.length - 1) {
				this.guide.Chapters.splice(index, 1);
				this.guide.Chapters.splice(index + 1, 0, chapter);
			}
			this.Autosize(true);
		},
		RemoveChapter(chapter) {
			if(confirm("do you really want to remove this chapter?  any changes to its content will be lost.")) {
				const index = this.guide.Chapters.indexOf(chapter);
				if(index >= 0) {
					this.guide.Chapters.splice(index, 1);
				}
			}
			this.Autosize(true);
		},
		AddChapter() {
			this.guide.Chapters.push({ Title: "", Markdown: "" });
			this.Autosize();
		},
		async Save() {
			this.saving = true;
			const chapters = this.guide.Chapters.map(c => { return { title: c.Title, markdown: c.Markdown }; });
			try {
				const result = await GuideApi.save(
					this.id,
					this.effectiveUrl,
					this.guide.Title,
					this.guide.Summary,
					this.guide.Level,
					this.guide.Tags,
					this.originalTags,
					chapters,
					this.correctionsOnly
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
		<form @submit.prevent=Save>
			<label title="title of the guide (for display)">
				<span class=label>title:</span>
				<span class=field><input maxlength=128 required v-model=guide.Title></span>
			</label>
			<label>
				<span class=label title="unique portion of guide url (alphanumeric with dots, dashes, and underscores)">url:</span>
				<ValidatingField :value=guide.ID :default=defaultUrl :original=this.id :urlCharsOnly=true :validate=validateId
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+', required: true}"
					@validated="(isValid, newValue) => OnValidated('ID', isValid, newValue)"
				></ValidatingField>
			</label>
			<label class=multiline title="introduction to or summary of the guide (use markdown)">
				<span class=label>summary:</span>
				<span class=field><textarea required rows="" cols="" v-model=guide.Summary></textarea></span>
			</label>
			<label title="guide difficulty level">
				<span class=label>level:</span>
				<span class=field><select v-model=guide.Level>
						<option>beginner</option>
						<option>intermediate</option>
						<option>advanced</option>
					</select></span>
			</label>
			<label>
				<span class=label>tags:</span>
				<TagsField :tags=guide.Tags @changed=TagsChanged></TagsField>
			</label>
			<fieldset v-for="(chapter, index) of guide.Chapters">
				<legend>
					chapter {{index + 1}}
					<a class="action up" href="#moveup" title="move this chapter earlier" v-if=index @click.prevent=MoveChapterUp(chapter)></a>
					<a class="action down" href="#movedown" title="move this chapter later" v-if="index < guide.Chapters.length - 1" @click.prevent=MoveChapterDown(chapter)></a>
					<a class="action del" href="#del" title="remove this chapter" @click.prevent=RemoveChapter(chapter)></a>
				</legend>
				<label :title="'title for chapter ' + (index + 1)">
					<span class=label>title:</span>
					<span class=field><input maxlength=128 required v-model=chapter.Title></span>
				</label>
				<label class=multiline :title="'content for chapter ' + (index + 1) + ' (use markdown)'">
					<span class=label>content:</span>
					<span class=field><textarea required rows="" cols="" v-model=chapter.Markdown></textarea></span>
				</label>
			</fieldset>
			<label>
				<span class=label></span>
				<span class=field><a class="action new" href="#addchapter" title="add a new blank chapter to the end" @click.prevent=AddChapter>add chapter</a></span>
			</label>
			<label v-if=guide.Published>
				<span class=label></span>
				<span class=field><span><input type=checkbox v-model=correctionsOnly> this edit is formatting / spelling / grammar only</span></span>
			</label>
			<button :disabled=!canSave class=save>save</button>
		</form>
	`
}).component("ValidatingField", ValidatingField)
	.component("TagsField", TagsField)
	.mount("#editguide");;
