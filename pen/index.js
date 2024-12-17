import "jquery";
import { createApp } from "vue";

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
	created: function() {
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			$.get("/api/story.php/list/" + this.stories.length)
				.done(result => {
					this.stories = this.stories.concat(result.Stories);
					this.hasMore = result.HasMore;
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<p v-if="!stories.length && !loading">
			no stories found!  i guess go read a book?
		</p>

		<article v-for="story in stories" :class="{series: story.NumStories}">
			<h2><a :href=story.ID>{{story.Title}}</a></h2>
			<p class=postmeta v-if="story.Instant || story.NumStories">
				<span v-if=story.NumStories>a series of {{story.NumStories}} stories</span>
				<span v-if=story.Instant>posted <time :datetime=story.Instant.DateTime :title=story.Instant.Tooltip v-html=story.Instant.Display></time></span>
			</p>
			<div class=description v-html=story.Description></div>
		</article>

		<p class=loading v-if=loading>loading more stories . . .</p>
		<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more stories</a></p>
	`
}).mount("#storylist");
