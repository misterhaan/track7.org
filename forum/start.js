$(function() {
	ko.applyBindings(window.startVM = new StartDiscussionViewModel());
});

function StartDiscussionViewModel() {
	var self = this;
	this.name = ko.observable("");
	this.contact = ko.observable("");
	this.title = ko.observable("");
	this.tags = ko.observableArray([]);
	this.message = ko.observable("");
	this.saving = ko.observable(false);

	this.Post = function() {
		self.saving(true);
		$.post("?ajax=post", {name: self.name(), contact: self.contact(), title: self.title(), taglist: self.tags().join(","), markdown: self.message()}, function(result) {
			if(result.fail) {
				alert(result.message);
				self.saving(false);
			} else
				window.location.href = result.url;
		});
	};
}
