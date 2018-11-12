$(function() {
	var main = new Vue({
		el: "main",
		data: {
			loading: false,
			updates: [],
			hasmore: false
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/updates/list", {oldest: this.oldest, oldid: this.lastid}, result => {
					if(!result.fail) {
						this.updates = this.updates.concat(result.updates);
						this.hasmore = result.hasmore;
						this.oldest = result.oldest;
						this.oldid = result.oldid;
					} else
						alert(result.message);
					this.loading = false;
				}, "json");
			}
		}
	});
});
