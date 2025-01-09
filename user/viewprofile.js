$(function() {
	var activity = new Vue({
		el: "#activity",
		data: {
			activity: [],
			loading: false,
			hasMore: false,
			error: ""
		},
		created: function() {
			this.user = $("h1").data("userid");
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/activity.php/byuser/" + this.user + "/" + this.activity.length).done(result => {
					this.activity = this.activity.concat(result.Activity);
					this.hasMore = result.HasMore;
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
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
