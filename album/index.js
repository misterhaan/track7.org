import "jquery";
import { createApp } from 'vue';
import 'tag';

createApp({
	data() {
		return {
			photos: [],
			error: null,
			loading: false,
			hasMore: false
		};
	},
	created: function() {
		this.tagid = document.location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/photo.php/list";
			if(this.tagid)
				url += "/" + this.tagid;
			if(this.photos.length)
				url += "/" + this.photos.length;

			$.get(url)
				.done(result => {
					this.photos = this.photos.concat(result.Photos);
					this.hasMore = result.HasMore;
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
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
			<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more photos</a></p>
			<p v-if="!photos.length && !loading">this album is empty!</p>
			<p class=error v-if=error>{{error}}</p>
	`
}).mount("#albumphotos");
