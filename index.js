import { createApp } from "vue";
import ActivityApi from "/api/activity.js";

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
		async Load() {
			this.loading = true;
			try {
				const result = await ActivityApi.list(this.activity.length);
				this.activity = this.activity.concat(result.Activity);
				this.hasMore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
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
				<p v-if=act.HasMore class="actions readmore"><a class=continue :href=act.URL>read more</a></p>
			</div>
		</article>
		<p class=loading v-if=loading>loading activity...</p>
		<p class=error v-if=error>{{error}}</p>
		<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href="/api/activity.php/list" @click.prevent=Load>show more activity</a></p>
	`
}).mount("#latestactivity");
