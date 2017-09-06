$(function() {
	$("#username").change(function() { ValidateField(this, "../?ajax=checkusername", "username", "validating username...", "username available."); });
	$("#displayname").change(function() { ValidateField(this, "../?ajax=checkname", "name", "validating display name...", "display name available.", "username will be used for display."); });
	$("#email").change(function() { ValidateField(this, "../?ajax=checkemail", "email", "validating e-mail address...", "looks like an e-mail address.", "e-mail address will be left blank."); });
	$("#website").change(function() { ValidateField(this, "../settings.php?ajax=checkurl", "url", "validating website url...", "url exists.", "no website listed"); });

	$("#newuser").submit(function() {
		$.post("../?ajax=register", {csrf: $("#csrf").val(), username: $("#username").val(), displayname: $("#displayname").val(), email: $("#email").val(), website: $("#website").val(), linkprofile: $("#linkprofile").val(), useavatar: +$("#useavatar").prop("checked")}, function(result) {
			if(!result.fail)
				if(result.continue)
					location.replace(result.continue);
				else
					location.replace("/user/settings.php");
			else
				alert(result.message);
		}, "json");
		return false;
	});

	$("#username").change();
	$("#displayname").change();
	$("#email").change();
	$("#website").change();
});
