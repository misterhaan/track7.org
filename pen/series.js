import "jquery";
import { createApp } from "vue";

createApp({
	name: "Series",
	data() {
		return {
			stories: [],
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.id = location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			$.get("/api/story.php/series/" + this.id)
				.done(result => {
					this.stories = this.stories.concat(result);
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
