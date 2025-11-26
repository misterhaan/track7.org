import { createApp } from "vue";
import StoryApi from "/api/story.js";

createApp({
	name: "Series",
	data() {
		return {
			stories: [],
			loading: false,
			error: ""
		};
	},
	created() {
		this.id = location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await StoryApi.series(this.id);
				this.stories = this.stories.concat(result);
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<p v-if="!stories.length && !loading">
			no stories found!  i guess go read a book?
		</p>

		<article v-for="story in stories">
			<h2><a :href=story.ID>{{story.Title}}</a></h2>
			<p class=meta v-if=story.Instant>
				<span>posted <time :datetime=story.Instant.DateTime :title=story.Instant.Tooltip v-html=story.Instant.Display></time></span>
			</p>
			<div class=description v-html=story.Description></div>
		</article>

		<p class=loading v-if=loading>loading more stories . . .</p>
	`
}).mount("#serieslist");
