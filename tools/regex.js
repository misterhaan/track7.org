$(function() {
	ko.applyBindings(window.vm = new RegexViewModel());

	if(!$(".tabcontent:visible").length) {  // this needs to be after the tab click handlers
		if($(location.hash).length)
			$("a[href$='" + location.hash + "']").click();
		else {
			$("a[href$='#match']").click();
			if(history.replaceState)
				history.replaceState(null, null, "#match");
			else
				location.hash = "#match";
		}
	}
});

function RegexViewModel() {
	var self = this;

	this.match = {
		pattern: ko.observable(""),
		subject: ko.observable(""),
		all: ko.observable(false),
		checked: ko.observable(false),
		matches: ko.observableArray([])
	};
	this.Match = function() {
		$.get("?ajax=match", {pattern: this.match.pattern(), subject: this.match.subject(), all: +this.match.all()}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				self.match.checked(true);
				self.match.matches(result.matches);
			}
		}, "json");
	};

	this.replace = {
		pattern: ko.observable(""),
		replacement: ko.observable(""),
		subject: ko.observable(""),
		replaced: ko.observable(false),
		result: ko.observable("")
	};
	this.Replace = function() {
		$.get("?ajax=replace", {pattern: this.replace.pattern(), replacement: this.replace.replacement(), subject: this.replace.subject()}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				self.replace.replaced(true);
				self.replace.result(result.replacedResult);
			}
		}, "json");
	};
}
