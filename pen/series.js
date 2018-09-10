$(function() {
	var serieslist = new Vue({
		el: "#serieslist",
		data: {
			stories: [],
			loading: false,
			error: false
		},
		created: function() {
			this.loading = true;
			$.get("/api/stories/series", {id: $("h1").data("series-id")}, result => {
				if(!result.fail) 
					this.stories = result.stories;
				else
					this.error = result.error;
			}, "json");
		}
	});
});
