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
		methods: {
			Load: function() {
				blog.loading = true;
				$.get("/api/blog/list", {tagid: $("p#taginfo").data("tagid"), before: oldest}, function(result) {
					if(result.fail)
						blog.errors.push(result.message);
					else {
						blog.entries = blog.entries.concat(result.entries);
						blog.hasMore = result.hasMore;
						oldest = result.lastdate;
					}
					blog.loading = false;
				});
			}
		}
	});
	blog.Load();
});
