$(function() {
	ko.applyBindings(window.ViewModel = new vm());
	if($("nav.tagcloud").length)
		window.ViewModel.LoadTags();
	window.ViewModel.LoadArt();
	$("#editdesc").hide();
	$("a[href$='#tagedit']").click(function(e) {
		$("#editdesc textarea").val($("#taginfo .editable").html());
		$("#editdesc").show().focus();
		$("a[href$='#tagedit']").hide();
		e.preventDefault();
	});
	$("a[href$='#save']").click(function(e) {
		$.post("/tags.php?ajax=setdesc&type=art", {id: $("#taginfo").data("tagid"), description: $("#editdesc textarea").val()}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail) {
				$("#taginfo .editable").html($("#editdesc textarea").val());
				$("a[href$='#tagedit']").show();
				$("#editdesc").hide();
			} else
				alert(result.message);
		});
		e.preventDefault();
	});
	$("a[href$='#cancel']").click(function(e) {
		$("a[href$='#tagedit']").show();
		$("#editdesc").hide();
		e.preventDefault();
	});
});

function vm() {
	var self = this;
	self.errors = ko.observableArray([]);

	self.tags = ko.observableArray([]);
	self.art = ko.observableArray([]);

	self.tagid = $("#taginfo").length ? $("#taginfo").data("tagid") : false;

	self.loadingArt = ko.observable(false);
	self.hasMoreArt = ko.observable(false);

	self.LoadTags = function() {
		self.tags.removeAll();
		$.get('/tags.php', {ajax: "list", type: "art"}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail)
				for(var t = 0; t < result.tags.length; t++)
					self.tags.push(result.tags[t]);
			else
				self.errors.push(result.message);
		});
	};

	self.LoadArt = function() {
		self.hasMoreArt(false);
		self.loadingArt(true);
		$.get("/art/", self.tagid ? {ajax: "art", tagid: self.tagid, beforetime: self.getLastTime(), beforeid: self.getLastId()} : {ajax: "art", beforetime: self.getLastTime(), beforeid: self.getLastId()}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail) {
				for(var a = 0; a < result.art.length; a++)
					self.art.push(result.art[a]);
				self.hasMoreArt(result.hasMore);
			}
			else
				self.errors.push(result.message);
			self.loadingArt(false);
		});
	};

	self.getLastTime = function() {
		if(self.art().length)
			return self.art()[self.art().length - 1].posted.timestamp;
		return '';
	}

	self.getLastId = function() {
		if(self.art().length)
			return self.art()[self.art().length - 1].id;
		return '';
	}
}
