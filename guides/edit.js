import { createApp } from 'vue';
import { NameToUrl, ValidatingField } from "validate";
import autosize from "autosize";
import { TagsField, FindAddedTags } from "tag";

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
			invalidFields: [],
			saving: false
		};
	},
	computed: {
		defaultUrl: function() {
			return NameToUrl(this.guide.Title);
		},
		effectiveUrl: function() {
			return this.guide.ID || this.defaultUrl;
		},
		canSave: function() {
			return !this.saving && this.guide.Title && this.guide.Summary && this.invalidFields.length <= 0 && this.guide.Chapters.every(p => p.Title && p.Markdown);
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
		Autosize(update) {
			this.$nextTick(() => {
				if(update)
					autosize.update($("textarea"));
				else
					autosize($("textarea"));
			});
		},
		Load() {
			$.get("/api/guide.php/edit/" + this.id).done(guide => {
				this.guide = guide;
				this.originalTags = this.guide.Tags;
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
			this.guide[fieldName] = newValue;
		},
		TagsChanged(value) {
			this.guide.Tags = value;
		},
		MoveChapterUp: function(chapter) {
			const index = this.guide.Chapters.indexOf(chapter);
			if(index > 0) {
				this.guide.Chapters.splice(index, 1);
				this.guide.Chapters.splice(index - 1, 0, chapter);
			}
			this.Autosize(true);
		},
		MoveChapterDown: function(chapter) {
			const index = this.guide.Chapters.indexOf(chapter);
			if(index >= 0 && index < this.guide.Chapters.length - 1) {
				this.guide.Chapters.splice(index, 1);
				this.guide.Chapters.splice(index + 1, 0, chapter);
			}
			this.Autosize(true);
		},
		RemoveChapter: function(chapter) {
			if(confirm("do you really want to remove this chapter?  any changes to its content will be lost.")) {
				const index = this.guide.Chapters.indexOf(chapter);
				if(index >= 0) {
					this.guide.Chapters.splice(index, 1);
				}
			}
			this.Autosize(true);
		},
		AddChapter: function() {
			this.guide.Chapters.push({ Title: "", Markdown: "" });
			this.Autosize();
		},
		Save() {
			this.saving = true;
			const url = "/api/guide.php/save/" + this.id;
			const data = {
				id: this.effectiveUrl,
				title: this.guide.Title,
				summary: this.guide.Summary,
				level: this.guide.Level,
				addTags: FindAddedTags(this.guide.Tags, this.originalTags),
				delTags: FindAddedTags(this.originalTags, this.guide.Tags),
				chapters: this.guide.Chapters.map(c => { return { title: c.Title, markdown: c.Markdown }; }),
				correctionsOnly: this.correctionsOnly
			};
			$.post({ url: url, data: JSON.stringify(data), contentType: "application/json; charset=utf-8" }).done(result => {
				window.location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form v-on:submit.prevent=Save>
			<label title="title of the guide (for display)">
				<span class=label>title:</span>
				<span class=field><input maxlength=128 required v-model=guide.Title></span>
			</label>
			<label>
				<span class=label title="unique portion of guide url (alphanumeric with dots, dashes, and underscores)">url:</span>
				<ValidatingField :value=guide.ID :default=defaultUrl :urlCharsOnly=true :validateUrl="'/api/guide.php/idAvailable/' + this.id + '='"
					msgChecking="validating url..." msgValid="url available" msgBlank="url required"
					inputAttributes="{maxlength: 32, pattern: '[a-z0-9\\-_]+'}"
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
				<legend>chapter {{index + 1}}</legend>
				<a class="action up" href="#moveup" title="move this chapter earlier" v-if=index v-on:click.prevent=MoveChapterUp(chapter)></a>
				<a class="action down" href="#movedown" title="move this chapter later" v-if="index < guide.Chapters.length - 1" v-on:click.prevent=MoveChapterDown(chapter)></a>
				<a class="action del" href="#del" title="remove this chapter" v-on:click.prevent=RemoveChapter(chapter)></a>
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
				<span class=field><a class="action new" href="#addchapter" title="add a new blank chapter to the end" v-on:click.prevent=AddChapter>add chapter</a></span>
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
