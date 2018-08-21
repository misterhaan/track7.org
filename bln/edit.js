$(function() {
	document.originalTags = [];
	LoadEntry();
	$("#title").keyup(function() {
		$("#url").prop("placeholder", $(this).val().replace(/ /g, "-").replace(/[^a-z0-9\.\-_]*/, ""));
	});
	// TODO:  set url field to only accept allowed characters
	// TODO:  set url field to verify uniqueness (guides do this)
	// TODO:  set tags field to only accept allowed characters
	// TODO:  set up tags field to suggest existing tags (guides do this)
	$("#editentry").submit(SaveEntry);
});

function LoadEntry() {
	if($("#editentry").data("entryid")) {
		$.get("/api/blog/edit", {id: $("#editentry").data("entryid")}, function(data, status, xhr) {
			var result = $.parseJSON(xhr.responseText);
			if(!result.fail) {
				$("#title").val(result.title);
				$("#url").val(result.url);
				$("#url").prop("placeholder", result.title.replace(/ /g, "-").replace(/[^a-z0-9\.\-_]*/, ""));
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
