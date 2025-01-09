import "jquery";
import { createApp } from "vue";

createApp({
	name: "LatestActivity",
	data() {
		return {
			activity: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/activity.php/list/" + this.activity.length).done(result => {
				this.activity = this.activity.concat(result.Activity);
				this.hasMore = result.HasMore;
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.loading = false;
			});
		}
	},
	template: /* html */ `
		<article class="activity" v-for="act in activity" :class=act.Type>
			<div class=whatwhen :title="act.Type + ' at ' + act.Instant.Tooltip">
				<time v-html=act.Instant.Display :datetime=act.Instant.DateTime></time>
			</div>
			<div>
				<h2>
					<span v-if="act.Type=='comment'">comment on </span>
					<a :href=act.URL>{{act.Title}}</a>
					by
					<a v-if=act.Contact :href=act.Contact :title="'contact ' + act.Name">{{act.Name}}</a>
					<span v-if=!act.Contact>{{act.Name}}</span>
				</h2>
				<div class=summary v-html=act.Preview></div>
				<p v-if=act.HasMore class=readmore><a class=continue :href=act.URL>read more</a></p>
			</div>
		</article>
		<p class=loading v-if=loading>loading activity...</p>
		<p class=error v-if=error>{{error}}</p>
		<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href="/api/activity.php/list" @click.prevent=Load>show more activity</a></p>
	`
}).mount("#latestactivity");
