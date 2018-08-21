$(function() {
	document.originalTags = [];
	LoadEntry();
	$("#title").change(function() {
		$("#url").attr("placeholder", $(this).val().toLowerCase().replace(/ /g, "-").replace(/[^a-z0-9\.\-_]*/, ""));
		if(!$("#url").val())
			ValidateUrl();
	});
	$("#url").keyup(function(e) {
		var field = $(this);
		field.val(field.val().toLowerCase().replace(/ /g, "-").replace(/[^a-z0-9\.\-_]/, ""));
	});
	$("#url").change(ValidateUrl);
	// TODO:  set tags field to only accept allowed characters
	// TODO:  set up tags field to suggest existing tags (guides do this)
	$("#editentry").submit(SaveEntry);
});

function ValidateUrl() {
	ValidateField($("#url"), "/api/blog/checkurl&id=" + $("#editentry").data("entryid"), "url", "validating url...", "url available", "url required");
}

function LoadEntry() {
	if($("#editentry").data("entryid")) {
		$.get("/api/blog/edit", {id: $("#editentry").data("entryid")}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail) {
				$("#url").val(result.url).change();  // load url before title so we don't validate the default url
				$("#title").val(result.title).change();
				$("#content").val(result.content);
				autosize.update($("#content"));
				$("#tags").val(result.tags);
				document.originalTags = result.tags.split(",");
			} else
				alert(result.message);
		});
	}
}

function SaveEntry() {
	var tags = $("#tags").val().split(",");
	$.post("/api/blog/save", { id: $("#editentry").data("entryid"), title: $("#title").val(),
		url: $("#url").val(), content: $("#content").val(),
		newtags: $.grep(tags, function(el) { return $.inArray(el, document.originalTags) == -1; }).join(","),
		deltags: $.grep(document.originalTags, function(el) { return $.inArray(el, tags) == -1; }).join(",")}, function(result) {
		if(!result.fail)
			window.location.href = result.url;
		else
			alert(result.message);
	});
	return false;
}
