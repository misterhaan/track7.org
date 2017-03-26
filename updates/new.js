$(function() {
	$("#editupdate").submit(function() {
		$("#save").prop('disabled', true).addClass("working");
		$.post("?ajax=save", {markdown: $("#markdown").val().trim(), posted: $("#posted").val().trim()}, function(result) {
			if(result.fail) {
				alert(result.message);
				$("#save").prop('disabled', false).removeClass("working");
			} else
				window.location.href = result.id;
		}, "json");
		return false;
	});
});