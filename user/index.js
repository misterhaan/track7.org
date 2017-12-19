$(function() {
	ko.applyBindings(ViewModel = new vm());
	window.ViewModel.GetUsers();

	HideChosenSortOption();

	$("a.droptrigger").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		var visdrop = $(this).siblings(".droplist");
		if(document.popup && document.popup[0] == visdrop[0]) {
			visdrop.hide();
			document.popup = false;
		} else {
			if(document.popup)
				document.popup.hide();
			visdrop.show();
			document.popup = visdrop;
		}
		return false;
	});

	$(".droplist a").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		var sortby = $(this).attr("href");
		$(this).parent().siblings(".droptrigger").attr("href", sortby).text($(this).text());
		$(this).parent().hide();
		ViewModel.Sort(sortby.substring(1));
		HideChosenSortOption();
		document.popup = false;
		return false;
	});
});

function vm() {
	var self = this;
	self.users = ko.observableArray([]);
	self.loadingUsers = ko.observable(false);
	self.hasMoreUsers = ko.observable(false);

	self.GetUsers = function() {
		self.hasMoreUsers(false);
		self.loadingUsers(true);
		$.get("/user/", {ajax: "listusers"}, function(data, status, xhr){
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail) {
				for(var u = 0; u < result.users.length; u++)
					self.users.push(result.users[u]);
				self.hasMoreUsers(result.hasMore);
			}
			self.loadingUsers(false);
		});
	};

	self.Sort = function(sortby) {
		var sortFunction = false;
		switch(sortby) {
			case "lastlogin":
				sortFunction = function(left, right) {
					return left.lastlogin.datetime == right.lastlogin.datetime ? 0 : left.lastlogin.datetime < right.lastlogin.datetime ? 1 : -1;
				};
				break;
			case "userlevel":
				sortFunction = function(left, right) {
					return NumericLevel(right.level) - NumericLevel(left.level);
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
		self.users.sort(sortFunction);
	};
}

function HideChosenSortOption() {
	var chosen = $("a.droptrigger").attr("href");
	$(".droplist a").show();
	$(".droplist a[href='" + chosen + "']").hide();
}

function NumericLevel(levelname) {
	switch(levelname) {
		case "admin": return 4;
		case "trusted": return 3;
		case "known": return 2;
		case "new": return 1;
	}
	return 0;
}
