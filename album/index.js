import { createApp } from "vue";
import "tag";
import PhotoApi from "/api/photo.js";

createApp({
	name: "Photos",
	data() {
		return {
			photos: [],
			error: null,
			loading: false,
			hasMore: false
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
				const result = await PhotoApi.list(this.tagid, this.photos.length);
				this.photos = this.photos.concat(result.Photos);
				this.hasMore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		}
	},
	template: /* html */ `
			<ol id=photogallery class=gallery v-if=photos.length>
				<li v-for="photo in photos">
					<a class="photo thumb" :href=photo.ID>
						<img :src="'/album/photos/' + photo.ID + '.jpg'">
						<span class=caption>{{photo.Title}}</span>
					</a>
				</li>
			</ol>

			<p class=loading v-if=loading>loading more photos . . .</p>
			<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage @click.prevent=Load>load more photos</a></p>
			<p v-if="!photos.length && !loading">this album is empty!</p>
			<p class=error v-if=error>{{error}}</p>
	`
}).mount("#albumphotos");
