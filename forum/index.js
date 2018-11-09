$(function() {
	var discussions = new Vue({
		el: "#discussionlist",
		data: {
			discussions: [],
			more: false,
			loading: true
		},
		created: function() {
			this.tagid = $("#taginfo").data("tagid");
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/forum/list", {tagid: this.tagid, before: this.latest}, result => {
					if(!result.fail) {
						this.discussions = this.discussions.concat(result.threads);
						this.more = result.more;
						this.latest = result.latest;
					} else
						alert(result.message);
					this.loading = false;
				}, "json");
			}
		}
	});
});
