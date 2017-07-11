$(function() {
	$("a.addfriend").click(friend);
	$("a.removefriend").click(friend);
	ko.applyBindings(window.ActivityVM = new ActivityViewModel(), $("#activity")[0]);
});

function friend() {
	var link = this;
	$.get(link.href, {}, function(result) {
		if(!result.fail) {
			if(link.className == "addfriend") {
				link.className = "removefriend";
				link.href = link.href.replace("add", "remove");
				$(link).text("remove friend");
				link.title = "remove " + link.title.substring(4, link.title.length - 12) + " from your friends";
			} else {
				link.className = "addfriend";
				link.href = link.href.replace("remove", "add");
				$(link).text("add friend");
				link.title = "add " + link.title.substring(7, link.title.length - 18) + " as a friend";
			}
		} else
			alert(result.message);
	}, "json");
	return false;
}

function ActivityViewModel() {
	var self = this;
	this.activity = ko.observableArray([]);
	this.latest = 0;
	this.loading = ko.observable(false);
	this.more = ko.observable(false);

	this.Load = function() {
		self.loading(true);
		$.get("?ajax=activity", {before: self.latest}, function(result) {
			if(!result.fail) {
				for(var a = 0; a < result.acts.length; a++)
					self.activity.push(result.acts[a]);
				self.latest = result.latest;
				self.more(result.more);
			} else
				alert(result.message);
			self.loading(false);
		}, "json");
	};
	this.Load();
}
