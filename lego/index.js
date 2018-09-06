$(function() {
	var legomodels = new Vue({
		el: "#legomodels",
		data: {
			legos: [],
			loading: true,
			hasMore: false,
			error: ""
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/legos/list", {before: this.oldest}, result => {
					if(!result.fail) {
						this.legos = this.legos.concat(result.legos);
						this.hasMore = result.hasMore;
						this.oldest = result.oldest;
					} else
						this.error = result.message;
					this.loading = false;
				}, "json");
			}
		}
	});
});
