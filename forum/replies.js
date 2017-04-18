$(function() {
	ko.applyBindings(window.RepliesVM = new RepliesViewModel());
});

function RepliesViewModel() {
	var self = this;
	this.userid = $("h1").data("user") || "";
	this.latest = "";
	this.replies = ko.observableArray([]);
	this.loading = ko.observable(false);
	this.more = ko.observable(false);

	this.Load = function() {
		self.loading(true);
		$.get("?ajax=list", {before: self.latest, userid: self.userid}, function(result) {
			self.loading(false);
			if(result.fail)
				alert(result.message);
			else {
				for(var r = 0; r < result.replies.length; r++)
					self.replies.push(ObserveReply(result.replies[r]));
				self.more(+result.more);
				self.latest = result.latest;
				Prism.highlightAll();
			}
		}, "json");
	};
	this.Load();

	this.EditReply = function(reply) {
		reply.editing(true);
		reply.savedmarkdown = reply.markdown();
		var ta = $("#r" + reply.id + " textarea");
		if(ta.data("asinit"))
			autosize.update(ta);
		else {
			autosize(ta);
			ta.data("asinit", true);
		}
		$("body").animate({scrollTop: ta.offset().top}, 750);
		ta.focus();
	};

	this.UneditReply = function(reply) {
		reply.editing(false);
		reply.markdown(reply.savedmarkdown);
		delete reply.savedmarkdown;
	};

	this.SaveReply = function(reply) {
		UpdateReply(reply, false);
	};

	this.StealthSaveReply = function(reply) {
		UpdateReply(reply, true);
	};

	this.DeleteReply = function(reply) {
		if(confirm("deleted replies cannot be recovered.  are you sure you want to delete?"))
			$.post("?ajax=delete", {reply: reply.id}, function(result) {
				if(result.fail)
					alert(result.message);
				else {
					self.replies.splice(self.replies.indexOf(reply), 1);
				}
			}, "json");
	};
}

function ObserveReply(reply) {
	reply.editing = ko.observable(false);
	reply.markdown = ko.observable(reply.markdown);
	reply.html = ko.observable(reply.html);
	reply.edits = ko.observableArray(reply.edits || []);
	return reply;
}

function UpdateReply(reply, stealth) {
	$.post(stealth ? "?ajax=stealthupdate" : "?ajax=update", {reply: reply.id, markdown: reply.markdown()}, function(result) {
		if(result.fail)
			alert(result.message);
		else {
			reply.html(result.html);
			reply.editing(false);
			if(result.edit)
				reply.edits.push(result.edit);
			Prism.highlightAll();
		}
	}, "json");
}
