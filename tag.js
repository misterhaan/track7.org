import "jquery";
import { createApp } from "vue";
import autosize from "autosize";

const tagcloud = document.querySelector(".tagcloud");
if(tagcloud)
	createApp({
		data() {
			return {
				tags: []
			};
		},
		created: function() {
			this.subsite = document.location.pathname.split("/")[1];
			this.pluralName = tagcloud.dataset.pluralName;
			this.Load();
		},
		methods: {
			Load: function() {
				$.get("/api/tag.php/list/" + this.subsite)
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
				<a :href="tag.Name + '/'" :title="pluralName + ' tagged ' + tag.Name" :data-count=tag.Count>{{tag.Name}}</a>
			</template>
		</template>
	`
	}).mount(".tagcloud");

const taginfo = document.querySelector("#taginfo");
const editLink = $("a[href$='#tagedit']");
if(taginfo && editLink) {
	const initialDescription = taginfo.querySelector(".editable")?.innerHTML;
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
			this.subsite = taginfo.dataset.subsite;
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
					url: "/api/tag.php/description/" + this.subsite + "/" + this.name,
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
