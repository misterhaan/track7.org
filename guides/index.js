$(function() {
	var guides = new Vue({
		el: "#guides",
		data: {
			hasTag: $("#taginfo").length > 0,
			guides: [],
			hasMore: false,
			loading: true,
			error: ""
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/guides/list", {tagid: $("p#taginfo").data("tagid"), before: this.oldest}, result => {
					if(!result.fail) {
						this.guides = this.guides.concat(result.guides);
						this.hasMore = result.hasMore;
						this.oldest = result.oldest;
					} else
						this.error = result.message;
					this.loading = false;
				});
			}
		}
	});
});
