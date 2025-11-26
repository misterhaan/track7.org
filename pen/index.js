import { createApp } from "vue";
import StoryApi from "/api/story.js";

createApp({
	name: "Stories",
	data() {
		return {
			stories: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created() {
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await StoryApi.list(this.stories.length);
				this.stories = this.stories.concat(result.Stories);
				this.hasMore = result.HasMore;
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

		<article v-for="story in stories" :class="{series: story.NumStories}">
			<h2><a :href=story.ID>{{story.Title}}</a></h2>
			<p class=meta v-if="story.Instant || story.NumStories">
				<span v-if=story.NumStories>a series of {{story.NumStories}} stories</span>
				<time v-if=story.Instant class=posted :datetime=story.Instant.DateTime :title="'posted ' + story.Instant.Tooltip" v-html=story.Instant.Display></time>
			</p>
			<div class=description v-html=story.Description></div>
		</article>

		<p class=loading v-if=loading>loading more stories . . .</p>
		<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage @click.prevent=Load>load more stories</a></p>
	`
}).mount("#storylist");
