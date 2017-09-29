$(function() {
	ko.applyBindings(window.GuideViewModel = new GuideViewModel(), $("#editguide")[0]);
	$("#title").change(function() {
		ValidateField(this, "/guides/edit.php?ajax=checktitle&id=" + window.GuideViewModel.id(), "title", "validating title...", "title available.");
		if(!$.trim(window.GuideViewModel.url()))
			ValidateField($("#url"), "/guides/edit.php?ajax=checkurl&id=" + window.GuideViewModel.id(), "url", "validating url...", "url available.");
	});
	$("#url").change(function() { ValidateField($("#url"), "/guides/edit.php?ajax=checkurl&id=" + window.GuideViewModel.id(), "url", "validating url...", "url available."); });
	if($("#editguide").data("url"))
		window.GuideViewModel.Load();
	else
		window.GuideViewModel.InitNewGuide();
	$("#tagSearch").keydown(function(e) {
		var vm = window.GuideViewModel;
		if(e.which == 8 && !vm.tagSearch()) {  // backspace key on a blank field
			vm.taglist.splice(-1);
			return;
		}
		if(e.which == 27) {  // esc
			vm.HideTagSuggestions();
			return;
		}
		if(e.which == 38)  // up arrow
			vm.PrevTag();
		if(e.which == 40)  // down arrow
			vm.NextTag();
		if(vm.showTagSuggestions() && vm.tagCursor() && (e.which == 9 || e.which == 13)) {  // tab (9), enter (13)
			vm.AddTag(vm.tagCursor());
			if(e.which == 13)  // enter key
				e.preventDefault();
			return;
		}
		vm.ShowTagSuggestions();
	}).dblclick(function() {
		window.GuideViewModel.ShowTagSuggestions();
	}).blur(function() {
		setTimeout(function() {
			window.GuideViewModel.HideTagSuggestions();
		}, 250);
	});
});

function GuideViewModel() {
	var self = this;

	self.id = ko.observable(0);
	self.status = ko.observable("draft");
	self.title = ko.observable("");
	self.defaultUrl = ko.pureComputed(function() {
		return self.title().trim().split(" ").join("-").replace(/[^a-z0-9\.\-_]+/g, "");
	});
	self.url = ko.observable("");
	self.summary = ko.observable("");
	self.level = ko.observable("intermediate");
	self.tagSearch = ko.observable("");
	self.tagCursor = ko.observable(false);
	self.definedTags = ko.observableArray([]);
	self.tagChoices = ko.pureComputed(function() {
		var choices = [];
		if(self.tagSearch() && self.definedTags().map(function(t) {return t.name;}).indexOf(self.tagSearch()) < 0)
			choices.push({name: self.tagSearch(), description: "this tag hasn’t been used yet."});
		for(var t = 0; t < self.definedTags().length; t++)
			if((!self.tagSearch() || self.definedTags()[t].name.indexOf(self.tagSearch()) >= 0) && self.taglist().indexOf(self.definedTags()[t]) < 0)
				choices.push(self.definedTags()[t]);
		return choices;
	});
	self.taglist = ko.observableArray([]);
	self.originalTaglist = [];

	self.pages = ko.observableArray([]);
	self.deletedPageIDs = [];

	self.showTagSuggestions = ko.observable(false);  // start with this off
	self.loading = ko.observable(false);
	self.correctionsOnly = ko.observable(false);

	self.InitNewGuide = function() {
		self.AddPage();
		$("#title").change();
		$.get("/guides/edit.php", {ajax: "tags"}, function(result) {
			self.definedTags(result.definedTags);
		}, "json");
	};

	self.Load = function() {
		self.loading(true);
		$.get("/guides/edit.php", {ajax: "get", url: $("#editguide").data("url")}, function(result) {
			if(!result.fail) {
				self.id(result.id);
				self.status(result.status);
				self.title(result.title);
				self.url(result.url);
				$("#title").change();
				$("#url").change();
				self.summary(result.summary);
				autosize.update($("textarea[data-bind*='summary']"));
				self.level(result.level);
				self.taglist(result.tags);
				for(var p = 0; p < result.pages.length; p++)
					self.pages.push(new Page(result.pages[p]));
				autosize($("textarea[data-bind*='markdown']"));
				self.originalTaglist = result.tags.map(function(t) {return t.name;});
				self.definedTags(result.definedTags);
			} else
				alert(result.message);
			self.loading(false);
		}, "json");
	};

	self.DelTag = function(tag) {
		self.taglist.remove(tag);
	};

	self.AddTag = function(tag) {
		self.taglist.push(tag);
		self.tagSearch("");
		self.HideTagSuggestions();
	};

	self.PrevTag = function() {
		var ch = self.tagChoices();
		if(self.tagCursor()) {
			var prev = false;
			for(var t = 0; t < ch.length; t++)
				if(ch[t] == self.tagCursor()) {
					if(prev)
						self.tagCursor(prev);
					else
						self.tagCursor(ch[ch.length - 1]);
					return;
				} else
					prev = ch[t];
		} else
			self.tagCursor(ch[ch.length - 1]);
	};

	self.NextTag = function() {
		if(self.tagCursor()) {
			var next = false;
			for(var t = self.tagChoices().length - 1; t >= 0; t--)
				if(self.tagChoices()[t] == self.tagCursor()) {
					if(next)
						self.tagCursor(next);
					else
						self.tagCursor(self.tagChoices()[0]);
					return;
				} else
					next = self.tagChoices()[t];
		} else
			self.tagCursor(self.tagChoices()[0]);
	};

	self.ShowTagSuggestions = function() {
		self.showTagSuggestions(true);
	};

	self.HideTagSuggestions = function() {
		self.showTagSuggestions(false);
		self.tagCursor(false);
	};

	self.AddPage = function() {
		self.pages.push(new Page({id: false, number: self.pages().length + 1, heading: "", markdown: ""}));
		autosize($("textarea[data-bind*='markdown']").last());
	}

	self.Save = function() {
		$("button.save").addClass("working").prop("disabled", true);
		var data = {
			id: self.id(),
			url: self.url(),
			title: self.title(),
			summary: self.summary(),
			level: self.level(),
			status: self.status(),
			correctiinsOnly: self.correctionsOnly(),
			pages: self.pages(),
			deletedPageIDs: self.deletedPageIDs,
			taglist: self.taglist().map(function(t) {return t.name;}),
			originalTaglist: self.originalTaglist
		};
		$.post("/guides/edit.php?ajax=save", {guidejson: ko.toJSON(data)}, function(result) {
			$("button.save").removeClass("working").prop("disabled", false);
			if(!result.fail)
				if($("#editguide").data("url"))
					window.location.href = "../" + result.url + "/1";
				else
					window.location.href = result.url + "/1";
			else
				alert(result.message);
		}, "json");
	};
}

function Page(page) {
	var self = this;

	self.id = page.id;
	self.number = ko.observable(page.number);
	self.heading = ko.observable(page.heading);
	self.markdown = ko.observable(page.markdown);

	self.MoveUp = function() {
		var index = window.GuideViewModel.pages.indexOf(self);
		if(index > 0) {
			window.GuideViewModel.pages()[index - 1].number(window.GuideViewModel.pages()[index].number());
			window.GuideViewModel.pages()[index].number(window.GuideViewModel.pages()[index].number() - 1);
			window.GuideViewModel.pages.sort(function(l, r) { return l.number() - r.number(); });
		}
		return false;
	};

	self.MoveDown = function() {
		var index = window.GuideViewModel.pages.indexOf(self);
		if(index < window.GuideViewModel.pages().length - 1) {
			window.GuideViewModel.pages()[index + 1].number(window.GuideViewModel.pages()[index].number());
			window.GuideViewModel.pages()[index].number(window.GuideViewModel.pages()[index].number() + 1);
			window.GuideViewModel.pages.sort(function(l, r) { return l.number() - r.number(); });
		}
		return false;
	};

	self.Remove = function() {
		if(confirm("do you really want to remove this page?  any changes to its content will be lost.")) {
			var index = window.GuideViewModel.pages.indexOf(self);
			window.GuideViewModel.pages.remove(self);
			for(var p = index; p < window.GuideViewModel.pages().length; p++)
				window.GuideViewModel.pages()[p].number(window.GuideViewModel.pages()[p].number() - 1);
			if(self.id)
				window.GuideViewModel.deletedPageIDs.push(self.id);
		}
		return false;
	};
}