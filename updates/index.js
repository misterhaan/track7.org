$(function() {
	ko.applyBindings(window.UpdatesVM = new UpdatesViewModel());
});

function UpdatesViewModel() {
	var self = this;
	this.loading = ko.observable(false);
	this.updates = ko.observableArray();
	this.hasmore = ko.observable(false);

	this.Load = function() {
		this.loading(true);
		var data = {};
		if(this.updates().length) {
			var last = this.updates()[this.updates().length - 1];
			data.oldest = last.posted.timestamp;
			data.oldid = last.id;
		}
		$.get("?ajax=list", data, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				self.updates.push.apply(self.updates, result.updates);
				self.hasmore(result.hasmore);
			}
			self.loading(false);
		}, "json");
	};
	this.Load();
}
