import "jquery";

$(function() {
	$("#codetypes section").click(function(e) {
		if(e.target.nodeName.toLowerCase() != "a")
			location.href = $(e.delegateTarget).find("h2 a").attr("href");
	});
	$("#codetypes section").css("cursor", "pointer");
});
