$(function() {
	ko.applyBindings(window.WorldVM = new WorldViewModel());
});

function WorldViewModel() {
	var self = this;
	this.name = ko.observable("");
	this.url = ko.observable("");
	this.engine = ko.observable("");
	this.desc = ko.observable("");
	this.dmzx = ko.observable("");
	this.released = ko.observable("");

	this.defaultUrl = ko.pureComputed(function() {
		return self.name().trim().replace(/\s/g, "-").replace(/[^a-z0-9\.\-_]*/g, "");
	});

	this.Load = function() {
		$.get("?ajax=load", {id: FindID()}, function(result) {
			if(!result.fail) {
				self.name(result.name);
				self.url(result.url);
				self.engine(result.engine);
				self.desc(result.desc);
				autosize.update($("#desc"));
				self.dmzx(result.dmzx || "");
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
		data.set("engine", self.engine());
		data.set("desc", self.desc());
		data.set("released", self.released());
		data.set("dmzx", self.dmzx());
		data.set("zip", $("#zip")[0].files[0]);
		data.set("screenshot", $("#screenshot")[0].files[0]);
		$.post({url: "?ajax=save", data: data, cache: false, contentType: false, processData: false, success: function(result) {
			if(!result.fail)
				window.location.href = ".#" + result.url;
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
