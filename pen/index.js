$(function() {
	var storylist = new Vue({
		el: "#storylist",
		data: {
			stories: [],
			error: false,
			loading: false
		},
		created: function() {
			this.loading = true;
			$.get("/api/stories/list", {}, result => {
				if(!result.fail)
					this.stories = result.stories;
				else
					this.error = result.message;
				this.loading = false;
			}, "json");
		}
	});
});
