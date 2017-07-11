$(function() {
	ko.applyBindings(window.ActivityVM = new ActivityViewModel(), $("#latestactivity")[0]);
});

function ActivityViewModel() {
	var self = this;
	this.activity = ko.observableArray([]);
	this.latest = 0;
	this.loading = ko.observable(false);

	this.Load = function() {
		self.loading(true);
		$.get("?ajax=activity", {before: self.latest}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				for(var a = 0; a < result.acts.length; a++)
					self.activity.push(result.acts[a]);
				self.latest = result.latest;
			}
			self.loading(false);
		}, "json");
	};
	this.Load();
}
