import { createApp } from "vue";
import "vote";
import "comment";
import GuideApi from "/api/guide.js";

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
	created() {
		this.id = location.pathname.split("/")[2];
		const summaryDiv = document.querySelector("#summary");
		this.summary = summaryDiv.innerHTML;
		summaryDiv.remove();
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const chapters = await GuideApi.chapters(this.id);
				this.chapters = chapters;
				this.$nextTick(() => {
					Prism.highlightAll();
				});
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
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

if(document.querySelector("nav.actions"))
	createApp({
		name: "GuideAdmin",
		data() {
			return {
				showPublishSuccess: false
			};
		},
		methods: {
			async Publish(event) {
				const id = event.target.href.split("/").pop();
				try {
					await GuideApi.publish(id);
					document.querySelector("a.del")?.remove();
					event.target.remove();
					this.showPublishSuccess = true;
					await new Promise(resolve => setTimeout(resolve, 3000));
					this.showPublishSuccess = false;
				} catch(error) {
					alert(error.message);
				}
			},
			async Delete(event) {
				if(confirm("do you really want to delete this guide?  it will be gone forever!"))
					try {
						const id = event.target.href.split("/").pop();
						await GuideApi.delete(id);
						location.href = "./";  // to index
					} catch(error) {
						alert(error.message);
					}
			}
		}
	}).mount("nav.actions");
