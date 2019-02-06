$(function() {
	var sortoptions = [
		{key: "lastlogin", display: "latest sign-in"},
		{key: "joined", display: "join date"},
		{key: "userlevel", display: "level"},
		{key: "username", display: "name"}
	];
	if($("#whodat").length)
		sortoptions.push({key: "friends", display: "friendship"});
	var main = new Vue({
		el: "main",
		data: {
			users: [],
			sortoptions: sortoptions,
			sort: sortoptions[0],
			loading: false,
			hasMore: false  // always false currently because it loads all users
		},
		created: function() {
			this.GetUsers();
		},
		methods: {
			GetUsers: function() {
				self.loading = true;
				$.get("/api/users/list", null, result => {
					if(!result.fail) {
						this.users = this.users.concat(result.users);
						this.hasMore = result.hasMore;
					} else
						alert(result.message);
					this.loading = false;
				}, "json");
			},
			ShowSortOptions: function(e) {
				var visdrop = $(e.target).siblings(".droplist");
				if(document.popup && document.popup[0] == visdrop[0]) {
					visdrop.hide();
					delete document.popup;
				} else {
					if(document.popup)
						document.popup.hide();
					visdrop.show();
					document.popup = visdrop;
				}
			},
			Sort: function(option) {
				this.sort = option;
				var sortFunction = false;
				switch(this.sort.key) {
					case "lastlogin":
						sortFunction = function(left, right) {
							return left.lastlogin.datetime == right.lastlogin.datetime ? 0 : left.lastlogin.datetime < right.lastlogin.datetime ? 1 : -1;
						};
						break;
					case "userlevel":
						sortFunction = function(left, right) {
							return +right.level - +left.level;
						};
						break;
					case "username":
						sortFunction = function(left, right) {
							return left.username.toLowerCase() < right.username.toLowerCase() ? -1 : 1;
						};
						break;
					case "joined":
						sortFunction = function(left, right) {
							return left.registered.datetime == right.registered.datetime ? 0 : left.registered.datetime < right.registered.datetime ? -1 : 1;
						};
						break;
					case "friends":
						sortFunction = function(left, right) {
							return +right.friend - +left.friend;
						};
						break;
				}
				if(sortFunction)
					this.users.sort(sortFunction);
				if(document.popup) {
					document.popup.hide();
					delete document.popup;
				}
			}
		}
	});
});
