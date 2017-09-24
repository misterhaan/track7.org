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
	else {
		window.GuideViewModel.AddPage();
		$("#title").change();
	}
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
	self.taglist = ko.observableArray([]);
	self.tags = ko.pureComputed({
		read: function() { return self.taglist().join(","); },
		write: function(value) { self.taglist(value.split(",")); }
	});
	self.originalTaglist = [];

	self.pages = ko.observableArray([]);
	self.deletedPageIDs = [];

	self.loading = ko.observable(false);
	self.correctionsOnly = ko.observable(false);

	self.Load = function() {
		self.loading(true);
		$.get("/guides/edit.php", {ajax: "get", url: $("#editguide").data("url")}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
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
				self.originalTaglist = result.tags;
			} else
				alert(result.message);
			self.loading(false);
		});
	};

	self.AddPage = function() {
		self.pages.push(new Page({id: false, number: self.pages().length + 1, heading: "", markdown: ""}));
		autosize($("textarea[data-bind*='markdown']").last());
	}

	self.Save = function() {
		$.post("/guides/edit.php?ajax=save", {guidejson: ko.toJSON(self)}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail)
				if($("#editguide").data("url"))
					window.location.href = "../" + result.url + "/1";
				else
					window.location.href = result.url + "/1";
			else
				alert(result.message);
		});
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