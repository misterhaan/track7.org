$(function() {
	var latestactivity = new Vue({
		el: "#latestactivity",
		data: {
			activity: [],
			latest: 0,
			more: false,
			loading: false
		},
		methods: {
			Load: function() {
				this.loading = true;
				var self = this;
				$.get("/api/activity/latest", {before: this.latest}, function(result) {
					if(result.fail)
						alert(result.message);
					else {
						self.activity = self.activity.concat(result.acts);
						self.latest = result.latest;
						self.more = result.more;
					}
					self.loading = false;
				}, "json");
			}
		}
	});
	latestactivity.Load();

	var features = new Vue({
		el: "#features",
		data: {
			features: [
				{id: "bln", name: "blog", desc: "read the blog"},
				{id: "album", name: "photo album", desc: "see my photos"},
				{id: "guides", name: "guides", desc: "learn how i’ve done things"},
				{id: "lego", name: "lego models", desc: "download instructions for custom lego models"},
				{id: "art", name: "visual art", desc: "see sketches and digital artwork"},
				{id: "pen", name: "stories", desc: "read short fiction and a poem"},
				{id: "code", name: "software", desc: "download free software with source code"},
				{id: "forum", name: "forums", desc: "join or start conversations"}
			]
		}
	});
});
