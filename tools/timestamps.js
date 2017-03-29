$(function() {
	ko.applyBindings(window.TimestampVM = new TimestampViewModel());
});

function TimestampViewModel() {
	var self = this;

	this.inputtype = ko.observable("");
	this.timestamp = ko.observable("");
	this.formatted = ko.observable("");
	this.zone = ko.observable("local");

	this.hasresults = ko.observable(false);
	this.resulttimestamp = ko.observable("");
	this.smart = ko.observable("");
	this.ago = ko.observable("");
	this.year = ko.observable("");
	this.month = ko.observable("");
	this.day = ko.observable("");
	this.weekday = ko.observable("");
	this.time = ko.observable("");

	this.Analyze = function() {
		var data = {type: this.inputtype(), zone: this.zone()};
		if(this.inputtype() == 'timestamp')
			data.timestamp = this.timestamp();
		if(this.inputtype() == 'formatted')
			data.formatted = this.formatted();
		$.get("?ajax=analyze", data, function(result) {
			if(result.fail)
				alert(result.message)
			else {
				self.resulttimestamp(result.timestamp);
				self.smart(result.smart);
				self.ago(result.ago);
				self.year(result.year);
				self.month(result.month);
				self.day(result.day);
				self.weekday(result.weekday);
				self.time(result.time);
				self.hasresults(true);
			}
		}, "json");
	};
}
