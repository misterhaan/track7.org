$(function() {
	$("#editupdate").submit(function() {
		$("#save").prop('disabled', true).addClass("working");
		$.post("/api/updates/add", {markdown: $("#markdown").val().trim(), posted: $("#posted").val().trim()}, result => {
			if(!result.fail)
				window.location.href = result.id;
			else {
				alert(result.message);
				$("#save").prop('disabled', false).removeClass("working");
			}
		}, "json");
		return false;
	});
});
