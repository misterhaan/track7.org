$(function() {
	var oldest = false;
	var blog = new Vue({
		el: "#blogentries",
		data: {
			errors: [],
			entries: [],
			loading: false,
			hasMore: false,
			hasTag: $("#taginfo").length > 0
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/blog/list", {tagid: $("p#taginfo").data("tagid"), before: this.oldest}, result => {
					if(result.fail)
						this.errors.push(result.message);
					else {
						this.entries = this.entries.concat(result.entries);
						this.hasMore = result.hasMore;
						this.oldest = result.lastdate;
					}
					blog.loading = false;
				});
			}
		}
	});
});
