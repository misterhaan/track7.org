$(function() {
	var photos = new Vue({
		el: "#albumphotos",
		data: {
			photos: [],
			error: "",
			loading: false,
			hasMore: false
		},
		created: function() {
			this.tagid = $("#taginfo").data("tagid");
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/photos/list", {tagid: this.tagid, before: this.oldest}, result => {
					if(!result.fail) {
						this.photos = this.photos.concat(result.photos);
						this.oldest = result.oldest;
						this.hasMore = result.hasMore;
					} else
						this.error = result.message;
					photos.loading = false;
				});
			}
		}
	});
});
