import { createApp } from "vue";
import UpdateApi from "/api/update.js";

createApp({
	name: "RecentUpdates",
	data() {
		return {
			updates: [],
			hasmore: false,
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
				const result = await UpdateApi.list(this.updates.length);
				this.updates = this.updates.concat(result.Updates);
				this.hasmore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		}
	},
	template: /* html */ `
		<article class="activity update" v-for="update in updates">
			<div class=whatwhen :title="'site update at ' + update.Instant.Tooltip">
				<time :datetime=update.Instant.DateTime v-html=update.Instant.Display></time>
			</div>
			<div>
				<h2></h2>
				<div class=summary v-html=update.HTML></div>
				<p><a :href=update.ID>{{update.Comments == 1 ? '1 comment' : update.Comments + ' comments'}}</a></p>
			</div>
		</article>

		<p class=error v-if=error>{{error}}</p>
		<nav class="showmore calltoaction" v-if="hasmore && !loading"><a class="action get" href="#loadmore" @click.prevent=Load>load older updates</a></nav>
		<p class=loading v-if=loading>loading . . .</p>
	`
}).mount("#recentupdates");
