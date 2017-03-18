$(function() {
	ko.applyBindings(window.ScriptVM = new ScriptViewModel());
});

function ScriptViewModel() {
	var self = this;
	this.name = ko.observable("");
	this.url = ko.observable("");
	this.usetype = ko.observable("");
	this.desc = ko.observable("");
	this.instr = ko.observable("");
	this.reqs = ko.observableArray([]);
	this.github = ko.observable("");
	this.wiki = ko.observable("");
	this.released = ko.observable("");
	this.link = ko.observable("");

	this.filelocation = ko.observable("");

	this.reqslist = ko.pureComputed({
		read: function() { return self.reqs().join(","); },
		write: function(value) { self.reqs(value ? value.split(",") : []); }
	});

	this.defaultUrl = ko.pureComputed(function() {
		return self.name().trim().replace(/\s/g, "-").replace(/[^a-z0-9\.\-_]*/g, "");
	});

	this.Load = function() {
		$.get("?ajax=load", {id: FindID()}, function(result) {
			if(!result.fail) {
				self.name(result.name);
				self.url(result.url);
				self.usetype(result.usetype);
				self.desc(result.desc);
				autosize.update($("#desc"));
				self.instr(result.instr);
				autosize.update($("#instr"));
				self.link(result.link);
				self.filelocation(result.link ? "link" : "upload");
				self.reqslist(result.reqslist);
				self.github(result.github);
				self.wiki(result.wiki);
				self.released(result.released);
			} else
				alert(result.message);
		}, "json");
	};
	if(FindID())
		this.Load();

	this.Save = function() {
		var data = new FormData();
		var id = FindID();
		if(id)
			data.set("id", id);
		data.set("name", self.name());
		data.set("url", self.url());
		data.set("usetype", self.usetype());
		data.set("desc", self.desc());
		data.set("instr", self.instr());
		data.set("reqslist", self.reqslist());
		data.set("github", self.github());
		data.set("wiki", self.wiki());
		data.set("released", self.released());
		if(self.filelocation() == "link")
			data.set("link", self.link());
		if(self.filelocation() == "upload")
			data.set("upload", $("#upload")[0].files[0]);
		$.post({url: "?ajax=save", data: data, cache: false, contentType: false, processData: false, success: function(result) {
			if(!result.fail)
				window.location.href = result.url;
			else
				alert(result.message);
		}, dataType: "json"});
	};
}

function FindID() {
	if(window.location.search) {
		var qs = window.location.search.substring(1);  // ignore the question mark
		if(qs) {
			qs = qs.split("&");
			for(var i = 0; i < qs.length; i++) {
				var v = qs[i].split("=");
				if(v[0] == "id")
					return v[1];
			}
		}
	}
	return false;
}
