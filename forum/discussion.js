$(function() {
	if(window.location.hash.substring(0, 2) == "#p")
		$.get("oldlink.php", {ajax: "reply", postid: window.location.hash.substring(2)}, function(result) {
			if(result.fail)
				alert(result.message);
			else
				window.location.hash = "#r" + result.id;
		}, "json");
	ko.applyBindings(window.RepliesVM = new RepliesViewModel());
	$("#addreply").submit(function() {
		$("#postreply").prop("disabled", true).addClass("waiting");
		var data = {};
		data.markdown = $("#newreply").val();
		if($("#authorname").length && $("#authorcontact").length) {
			data.name = $("#authorname").val();
			data.contact = $("#authorcontact").val();
		}
		$.post("?ajax=addreply", data, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				RepliesVM.replies.push(ObserveReply(result.reply));
				$("#newreply").val("");
			}
			$("#postreply").prop("disabled", false).removeClass("waiting");
		}, "json");
		return false;
	});
});

function RepliesViewModel() {
	var self = this;
	this.replies = ko.observableArray([]);

	this.Load = function() {
		$.get("?ajax=replies", {}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				for(var r = 0; r < result.replies.length; r++)
					self.replies.push(ObserveReply(result.replies[r]));
				if(window.location.hash && $(window.location.hash).length)
					$("body").animate({scrollTop: $(window.location.hash).offset().top}, 750);
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
			$.post("replies.php?ajax=delete", {reply: reply.id}, function(result) {
				if(result.fail)
					alert(result.message);
				else if(result.deletedDiscussions)
					window.location.href = ".";
				else
					self.replies.splice(self.replies.indexOf(reply), 1);
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
	$.post(stealth ? "replies.php?ajax=stealthupdate" : "replies.php?ajax=update", {reply: reply.id, markdown: reply.markdown()}, function(result) {
		if(result.fail)
			alert(result.message);
		else {
			reply.html(result.html);
			reply.editing(false);
			if(result.edit)
				reply.edits.push(result.edit);
		}
	}, "json");
}
