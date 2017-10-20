$(function() {
	ko.applyBindings(window.gbvm = new GBVM());
	window.gbvm.Load(1);
});

function GBVM() {
	var self = this;
	// these are loaded from the database
	this.num = ko.observable(0);
	this.num.subscribe(function() {
		$("#nextentry").val(+self.num() + 1);
	});
	this.instant = ko.observable({display: "", timestamp: 0});
	this.fullentry = ko.observable("");
	this.version = ko.observable(-1);
	this.name = ko.observable("");
	// these are calculated from fullentry and probably are also form fields
	this.email = ko.observable("");
	this.website = ko.observable("");
	this.comment = ko.observable("");

	// list of what's been done while this page has been open
	this.results = ko.observableArray([]);

	this.Load = function(num) {
		$.get("?ajax=entry", {id: num || +$("#nextentry").val()}, function(result) {
			if(result.fail)
				alert(result.message);
			else if(result.entry) {
				self.num(result.entry.id);
				self.instant(result.entry.instant);
				self.fullentry(result.entry.comments);
				self.version(result.entry.version);
				self.name(result.entry.name);
				self.email(result.entry.email);
				self.website(result.entry.website);
				self.comment(result.entry.comment);
			} else
				alert("no more entries i guess.");
		}, "json");
	};

	this.Skip = function() {
		self.results.unshift("skipped entry #" + self.num());
		self.Load();
	};

	this.SaveMessage = function() {
		var from = $("input[name='msgfrom']:checked").val();
		var username = $("input[name='msgusername']").val();
		var email = self.email();
		if(email)
			email = "mailto:" + email;
		$.post("?ajax=savemessage", {name: self.name(), from: from, email: email, website: self.website(), username: username, comment: self.comment(), instant: self.instant().timestamp}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				self.results.unshift("saved entry #" + self.num() + " as a message to misterhaan");
				self.Load();
			}
		}, "json");
	};

	this.SaveComment = function() {
		var from = $("input[name='commentfrom']:checked").val();
		var username = $("input[name='commentusername']").val();
		var email = self.email();
		if(email)
			email = "mailto:" + email;
		$.post("?ajax=saveartcomment", {arturl: $("#arturl").val(), name: self.name(), from: from, email: email, website: self.website(), username: username, comment: self.comment(), instant: self.instant().timestamp}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				self.results.unshift("saved entry #" + self.num() + " as a comment on " + $("#arturl").val());
				self.Load();
			}
		}, "json");
	};
}
