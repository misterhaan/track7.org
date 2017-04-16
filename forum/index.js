$(function() {
	ko.applyBindings(window.ThreadsModel = new ThreadsViewModel());
	$("#editdesc").hide();
	$("a[href$='#tagedit']").click(function(e) {
		$("#editdesc textarea").val($("#taginfo .editable").html());
		$("#editdesc").show().focus();
		$("a[href$='#tagedit']").hide();
		e.preventDefault();
	});
	$("a[href$='#save']").click(function(e) {
		$.post("/tags.php?ajax=setdesc&type=guide", {id: $("#taginfo").data("tagid"), description: $("#editdesc textarea").val()}, function(data, status, xhr) {
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

function ThreadsViewModel() {
	var self = this;
	this.tags = ko.observableArray([]);
	this.tagid = $("#taginfo").length ? $("#taginfo").data("tagid") : false;
	this.discussions = ko.observableArray([]);
	this.latest = "";
	this.more = ko.observable(false);
	this.loading = ko.observable(false);

	this.errors = ko.observableArray([]);

	this.LoadTags = function() {
		self.tags.removeAll();
		$.get('/tags.php', {ajax: "list", type: "forum"}, function(result) {
			if(!result.fail)
				for(var t = 0; t < result.tags.length; t++)
					self.tags.push(result.tags[t]);
			else
				self.errors.push(result.message);
		}, "json");
	};
	if($("nav.tagcloud").length)
		this.LoadTags();

	this.Load = function() {
		self.loading(true);
		$.get("/forum/", {ajax: "list", tagid: +self.tagid, before: self.latest}, function(result) {
			self.loading(false);
			if(result.fail)
				alert(result.message);
			else {
				for(var d = 0; d < result.discussions.length; d++)
					self.discussions.push(result.discussions[d]);
				self.latest = result.latest;
				self.more(+result.more);
			}
		}, "json");
	};
	this.Load();
}
