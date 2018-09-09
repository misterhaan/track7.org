$(function() {
	var visualart = new Vue({
		el: "#visualart",
		data: {
			arts: [],
			loading: false,
			hasMore: false,
			error: ""
		},
		created: function() {
			this.tagid = $("#taginfo").data("tagid");
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/art/list", {tagid: this.tagid, beforetime: this.oldest, beforeid: this.lastid}, result => {
					if(!result.fail) {
						this.arts = this.arts.concat(result.arts);
						this.oldest = result.oldest;
						this.lastid = result.lastid;
						this.hasMore = result.hasMore;
					} else
						this.error = result.message;
					this.loading = false;
				}, "json");
			}
		}
	});
});
