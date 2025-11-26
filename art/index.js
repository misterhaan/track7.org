import { createApp } from "vue";
import "tag";
import ArtApi from "/api/art.js";

createApp({
	name: "VisualArt",
	data() {
		return {
			arts: [],
			hasMore: false,
			loading: false,
			error: null
		};
	},
	created() {
		this.tagid = location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await ArtApi.list(this.tagid, this.arts.length);
				this.arts = this.arts.concat(result.Art);
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
	<p v-if="!arts.length && !loading">this gallery is empty!</p>

	<ol id=artgallery class=gallery>
		<li v-for="art in arts">
			<a class="art thumb" :href=art.ID>
				<img :src="'/art/img/' + art.ID + '-prev.' + art.Ext">
			</a>
		</li>
	</ol>

	<p class=loading v-if=loading>loading more art . . .</p>
	<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage @click.prevent=Load>load more art</a></p>
`
}).mount("#visualart");
