$(function() {
	var activity = new Vue({
		el: "#activity",
		data: {
			activity: [],
			loading: false,
			more: false
		},
		created: function() {
			this.user = $("h1").data("userid");
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/activity/user", {before: this.latest, user: this.user}, result => {
					if(!result.fail) {
						this.activity = this.activity.concat(result.acts);
						this.latest = result.latest;
						this.more = result.more;
					} else
						alert(result.message);
					this.loading = false;
				}, "json");
			}
		}
	});
	$("a.addfriend").click(friend);
	$("a.removefriend").click(friend);
});

function friend() {
	$.get(this.href, {}, result => {
		if(!result.fail) {
			if(this.className == "addfriend") {
				this.className = "removefriend";
				this.href = this.href.replace("add", "remove");
				$(this).text("remove friend");
				this.title = "remove " + this.title.substring(4, this.title.length - 12) + " from your friends";
				$("h1").addClass("friend");
			} else {
				this.className = "addfriend";
				this.href = this.href.replace("remove", "add");
				$(this).text("add friend");
				this.title = "add " + this.title.substring(7, this.title.length - 18) + " as a friend";
				$("h1").removeClass("friend");
			}
		} else
			alert(result.message);
	}, "json");
	return false;
}
