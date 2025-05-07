import "jquery";
import { createApp } from "vue";
import "vote";
import "comment";

createApp({
	name: "Chapters",
	data() {
		return {
			chapters: [],
			summary: "",
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.id = window.location.pathname.split("/")[2];
		const summaryDiv = $("#summary");
		this.summary = summaryDiv.html();
		summaryDiv.remove();
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;
			$.get("/api/guide.php/chapters/" + this.id)
				.done(chapters => {
					this.chapters = chapters;
					this.$nextTick(() => {
						Prism.highlightAll();
					});
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
		}
	},
	template: /* html */ `
		<nav class=toc v-if=chapters.length>
			<header>chapters</header>
			<ol>
				<li v-for="chapter in chapters"><a :href="'#ch' + chapter.Number" :title="'jump to chapter:  ' + chapter.Title">{{chapter.Title}}</a></li>
			</ol>
		</nav>

		<div id=summary v-html=summary></div>

		<section v-for="chapter in chapters" :id="'ch' + chapter.Number">
			<h2>{{chapter.Title}}</h2>
			<div class=chapter v-html=chapter.HTML></div>
		</section>
		`
}).mount("#guidechapters");

if($("nav.actions").length)
	createApp({
		name: "GuideAdmin",
		methods: {
			Publish(event) {
				$.post(event.target.href).done(() => {
					$("a.del").remove();
					const nav = $(event.target).parent();
					$(event.target).remove();
					nav.append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
				}).fail(request => {
					alert(request.responseText);
				});
			},
			Delete(event) {
				if(confirm("do you really want to delete this guide?  it will be gone forever!"))
					$.ajax({ url: event.target.href, method: "DELETE" }).done(() => {
						window.location.href = "./";  // to index
					}).fail(request => {
						alert(request.responseText);
					});
			}
		}
	}).mount("nav.actions");
