import "jquery";
import { createApp } from "vue";
import autosize from "autosize";

const subsite = document.location.pathname.split("/")[1];


const tagcloud = document.querySelector(".tagcloud");
if(tagcloud)
	createApp({
		data() {
			return {
				tags: []
			};
		},
		created: function() {
			this.pluralName = tagcloud.dataset.pluralName;
			this.Load();
		},
		methods: {
			Load: function() {
				$.get("/api/tag.php/list/" + subsite)
					.done(result => {
						this.tags = result;
					}).fail(request => {
						alert(request.responseText);
					});
			}
		},
		template: /* html */ `
		<template v-if=tags.length>
			<header>tags</header>
			<template v-for="tag in tags">
				<a :href="tag.Name.replaceAll(' ', '+') + '/'" :title="pluralName + ' tagged ' + tag.Name" :data-count=tag.Count>{{tag.Name}}</a>
			</template>
		</template>
	`
	}).mount(".tagcloud");

const taginfo = document.querySelector("#taginfo");
const editLink = $("a[href$='#tagedit']");
if(taginfo && editLink.length) {
	const tagDescription = taginfo.querySelector(".editable");
	const initialDescription = tagDescription?.innerHTML;
	if(tagDescription)
		tagDescription.textContent = "";

	createApp({
		data() {
			return {
				description: initialDescription,
				editing: false,
				saving: false
			};
		},
		created: function() {
			editLink.click(event => {
				this.StartEdit();
				event.preventDefault();
			});
			this.name = taginfo.dataset.name;
		},
		methods: {
			StartEdit: function() {
				this.editing = true;
				this.oldDescription = this.description;
				editLink.hide();
				this.$nextTick(() => {
					this.$refs.editField.focus();
					autosize(this.$refs.editField);
				});
			},
			CancelEdit: function() {
				this.editing = false;
				this.description = this.oldDescription;
				delete this.oldDescription;
				editLink.show();
			},
			SaveEdit: function() {
				this.saving = true;
				$.ajax({
					url: "/api/tag.php/description/" + subsite + "/" + this.name,
					type: "PUT",
					data: this.description
				}).done(() => {
					this.editing = false;
					editLink.show();
				}).fail(request => {
					alert(request.responseText);
				}).always(() => {
					this.saving = false;
				});
			}
		}
	}).mount("#taginfo");
}

export const TagsField = {
	props: [
		"tags"
	],
	emits: [
		"changed"
	],
	data() {
		return {
			tagArray: [],
			search: "",
			showSuggestions: false,
			cursor: ""
		};
	},
	created() {
		$.get("/api/tag.php/list/" + subsite).done(result => {
			this.allTags = result.map(tag => tag.Name);
		}).fail(request => {
			alert(request.responseText);
		});
	},
	watch: {
		tags(value) {
			this.tagArray = value ? value.split(",") : [];
		},
		search(value) {
			this.search = value.toLowerCase().replace(/[^a-z0-9\.]/, "");
		}
	},
	computed: {
		choices() {
			if(this.search) {
				const choices = [];
				if(this.allTags.indexOf(this.search) < 0 && this.tagArray.indexOf(this.search) < 0)
					choices.push("“" + this.search + "”");
				for(let t = 0; t < this.allTags.length; t++)
					if(this.allTags[t].indexOf(this.search) >= 0 && this.tagArray.indexOf(this.allTags[t]) < 0)
						choices.push(this.allTags[t].replace(new RegExp(this.search.replace(/\./, "\\."), "gi"), "<em>$&</em>"));
				return choices;
			}
			return this.allTags.filter(tag => { return this.tagArray.indexOf(tag) < 0; });
		}
	},
	methods: {
		Add(name) {
			if(name) {
				this.search = "";
				this.HideSuggest();
				this.tagArray.push(name.replace(/<[^>]*>|“|”/gi, ""));
				this.Changed();
			}
		},
		Delete(tag) {
			const index = this.tagArray.indexOf(tag);
			if(index > -1) {
				this.tagArray.splice(index, 1);
				this.Changed();
			}
		},
		Backspace() {
			if(!this.search && this.tagArray.length > 0) {
				this.tagArray.splice(this.tagArray.length - 1, 1);
				this.Changed();
			}
		},
		AddTypedTag() {
			if(this.search && (this.choices[0] == "“" + this.search + "”" || this.choices[0] == "<em>" + this.search + "</em>"))
				this.Add(this.search);
		},
		SearchKeyDown(event) {
			if(event.key == "Backspace" && this.search || /^[A-Za-z0-9\.]$/.test(event.key))
				this.showSuggestions = true;
		},
		ShowSuggest() {
			this.showSuggestions = true;
		},
		HideSuggest() {
			this.cursor = '';
			this.showSuggestions = false;
		},
		DelayedHideSuggest() {
			setTimeout(this.HideSuggest, 250);  // blur needs to be delayed or click events don't get through
		},
		Next() {
			if(this.cursor)
				for(let t = 0; t < this.choices.length - 1; t++)
					if(this.choices[t].replace(/<[^>]>/g, "") == this.cursor) {
						this.cursor = this.choices[t + 1].replace(/<[^>]>/g, "");
						this.showSuggestions = true;
						return;
					}
			this.cursor = this.choices[0].replace(/<[^>]>/g, "");
			this.showSuggestions = true;
		},
		Prev() {
			if(this.cursor)
				for(let t = 1; t < this.choices.length; t++)
					if(this.choices[t].replace(/<[^>]>/g, "") == this.cursor) {
						this.cursor = this.choices[t - 1].replace(/<[^>]>/g, "");
						this.showSuggestions = true;
						return;
					}
			this.cursor = this.choices[this.choices.length - 1].replace(/<[^>]>/g, "");
			this.showSuggestions = true;
		},
		AddCursorTag() {
			if(this.cursor)
				this.Add(this.cursor);
		},
		Changed() {
			this.$emit("changed", this.tagArray.join(","));
		}
	},
	template: /* html */ `
		<span class="field list">
			<span class=chosen v-for="tag in tagArray"><span>{{tag}}</span><a class="action del" href="#deltag" @click.prevent=Delete(tag) :title="'remove the ' + tag + ' tag'" tabindex=-1></a></span>
			<span class=suggestinput>
				<input id=tags autocomplete=off v-model=search @keydown.down.prevent=Next @keydown.up.prevent=Prev @dblclick=ShowSuggest @blur=DelayedHideSuggest
					@keydown.esc=HideSuggest @keydown.enter.prevent=AddCursorTag @keydown.,.prevent=AddTypedTag @keydown.tab=AddCursorTag @keydown.backspace=Backspace @keydown=SearchKeyDown>
				<span class=suggestions v-if=showSuggestions>
					<span v-for="tag in choices" v-html=tag :class="{selected: tag.replace(/<[^>]>/g, '') == cursor}" :title="'add the ' + tag + ' tag'" @click=Add(tag)></span>
				</span>
			</span>
		</span>
	`
};
