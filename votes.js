$(function() {
	var votes = new Vue({
		el: "#votes",
		data: {
			votes: [],
			more: 0,
			loading: false
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/votes/list", {oldest: this.oldest}, result => {
					if(!result.fail) {
						this.votes = this.votes.concat(result.votes);
						this.oldest = result.oldest;
						this.more = result.more;
					} else
						alert(result.message);
					this.loading = false;
				}, "json");
			},
			Delete: function(vote) {
				$.post("/api/votes/delete", {type: vote.type, id: vote.id, item: vote.item}, result => {
					if(result.fail)
						alert(result.message);
					else if(result.deleted) {
						var v = this.votes.indexOf(vote);
						if(v > -1)
							this.votes.splice(v, 1);
					} else
						alert("vote not deleted");
				}, "json");
			}
		}
	});
});
