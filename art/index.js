import "jquery";
import { createApp } from "vue";
import "tag";

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
	created: function() {
		this.tagid = window.location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/art.php/list";
			if(this.tagid)
				url += "/" + this.tagid;
			if(this.arts.length)
				url += "/" + this.arts.length;

			$.get(url)
				.done(result => {
					this.arts = this.arts.concat(result.Art);
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
