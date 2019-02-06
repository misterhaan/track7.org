$(function() {
	$("#username").change(function() { ValidateField(this, "/api/settings/checkUsername", "username", "validating username...", "username available."); });
	$("#displayname").change(function() { ValidateField(this, "/api/settings/checkName", "name", "validating display name...", "display name available.", "username will be used for display."); });
	$("#email").change(function() { ValidateField(this, "/api/settings/checkEmail", "email", "validating e-mail address...", "looks like an e-mail address.", "e-mail address will be left blank."); });
	$("#website").change(function() { ValidateField(this, "/api/settings/checkUrl", "url", "validating website url...", "url exists.", "no website listed"); });

	$("#newuser").submit(function() {
		$.post("/api/users/register", {csrf: $("#csrf").val(), username: $("#username").val(), displayname: $("#displayname").val(), email: $("#email").val(), website: $("#website").val(), linkprofile: $("#linkprofile").val(), useavatar: +$("#useavatar").prop("checked")}, result => {
			if(!result.fail)
				location.replace(result.continue || "/user/settings.php");
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
