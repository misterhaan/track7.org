$(function() {
	$("a[href$='#profile']").click(function() {
		if(!$("#profile").data("loaded"))
			$.get("/user/settings.php?ajax=loadprofile", {}, function(result) {
				if(!result.fail) {
					$("#username").val(result.username);
					$("#username").change();
					$("#displayname").val(result.displayname);
					$("#displayname").change();
					$("#profile").data("loaded", true);
				} else
					alert(result.message);
			}, "json");
	});

	$("a[href$='#timezone']").click(function() {
		if(!$("#timezone").data("loaded"))
			$.get("/user/settings.php?ajax=loadtime", {}, function(result) {
				if(!result.fail) {
					$("#currenttime").val(result.currenttime);
					$("#dst").prop("checked", result.dst);
					$("#timezone").data("loaded", true);
				} else
					alert(result.message);
			}, "json");
	});

	$("a[href$='#contact']").click(function() {
		if(!$("#contact").data("loaded"))
			$.get("/user/settings.php?ajax=loadcontact", {}, function(result) {
				if(!result.fail) {
					$("#email").val(result.email).change();
					var vis = $("#vis_email");
					vis.attr("data-value", result.vis_email);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_email + "']").text());
					$("#website").val(result.website).change();
					vis = $("#vis_website");
					vis.attr("data-value", result.vis_website);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_website + "']").text());
					$("#twitter").val(result.twitter).change();
					vis = $("#vis_twitter");
					vis.attr("data-value", result.vis_twitter);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_twitter + "']").text());
					$("#google").val(result.google).change();
					vis = $("#vis_google");
					vis.attr("data-value", result.vis_google);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_google + "']").text());
					$("#facebook").val(result.facebook).change();
					vis = $("#vis_facebook");
					vis.attr("data-value", result.vis_facebook);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_facebook + "']").text());
					$("#steam").val(result.steam).change();
					vis = $("#vis_steam");
					vis.attr("data-value", result.vis_steam);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_steam + "']").text());
					$("#contact").data("loaded", true);
				} else
					alert(result.message);
			}, "json");
	});

	$("a[href$='#notification']").click(function() {
		if(!$("#notification").data("loaded"))
			$.get("/user/settings.php?ajax=loadnotification", {}, function(result) {
				if(!result.fail) {
					$("#emaillabel").text(result.email);
					$("#notifymsg")[0].checked = result.emailnewmsg;
					$("#notification").data("loaded", true);
				} else
					alert(result.message);
			}, "json");
	});

	if(!$(".tabcontent:visible").length) {  // this needs to be after the tab click handlers
		if($(location.hash).length)
			$("a[href$='" + location.hash + "']").click();
		else {
			$("a[href$='#profile']").click();
			if(history.replaceState)
				history.replaceState(null, null, "#profile");
			else
				location.hash = "#profile";
		}
	}

	$("#username").change(function() { ValidateField(this, "./?ajax=checkusername", "username", "validating username...", "username available."); });
	$("#displayname").change(function() { ValidateField(this, "./?ajax=checkname", "name", "validating display name...", "display name available.", "username will be used for display."); });

	$("#profile").submit(function() {
		$("#profile button.save").prop("disabled", true).addClass("working");
		$.post("/user/settings.php?ajax=saveprofile", {username: $("#username").val(), displayname: $("#displayname").val()}, function(result) {
			$("#profile button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				$("a[href='" + $("#whodat").attr("href") + "']").attr("href", "/user/" + result.username + "/");
				$("#whodat").contents().get(0).nodeValue = result.displayname;
			} else
				alert(result.message);
		}, "json");
		return false;
	});

	$("#detecttime").click(function() {
		var now = new Date();
		var hour = now.getHours();
		$("#currenttime").val((hour == 0 ? 12 : hour > 12 ? hour - 12 : hour) + (now.getMinutes() < 10 ? ":0" : ":") + now.getMinutes() + (hour >= 12 ? " pm" : " am"));
		var jan = new Date(now.getFullYear(), 1, 1);
		var jul = new Date(now.getFullYear(), 7, 1);
		$("#dst").prop("checked", jan.getTimezoneOffset() != jul.getTimezoneOffset());
		return false;
	});

	$("#timezone").submit(function() {
		$("#timezone button.save").prop("disabled", true).addClass("working");
		$.post("/user/settings.php?ajax=savetime", {currenttime: $("#currenttime").val(), dst: $("#dst").prop("checked")}, function(result) {
			$("#timezone button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success?
			} else
				alert(result.message);
		}, "json");
		return false;
	});

	$("#email").change(function() { ValidateField(this, "./?ajax=checkemail", "email", "validating e-mail address...", "looks like an e-mail address.", "e-mail address will be left blank."); });
	$("#website").change(function() { ValidateField(this, "?ajax=checkurl", "url", "validating website url...", "url exists.", "no website listed."); });
	$("#twitter").change(function() { ValidateField(this, "?ajax=checktwitter", "twitter", "validating twitter username...", "valid twitter handle.", "no twitter profile listed."); });
	$("#google").change(function() { ValidateField(this, "?ajax=checkgoogle", "google", "validating google+ profile...", "valid google+ profile.", "no google+ profile listed."); });
	$("#facebook").change(function() { ValidateField(this, "?ajax=checkfacebook", "facebook", "validating facebook username...", "valid facebook profile.", "no facebook profile listed."); });
	$("#steam").change(function() { ValidateField(this, "?ajax=checksteam", "steam", "validating steam profile...", "valid steam profile.", "no steam profile listed."); });

	$("a.visibility.droptrigger").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		var visdrop = $(this).siblings(".droplist");
		if(document.popup && document.popup[0] == visdrop[0]) {
			visdrop.hide();
			document.popup = false;
		} else {
			if(document.popup)
				document.popup.hide();
			visdrop.show();
			document.popup = visdrop;
		}
		return false;
	});

	$("body").click(function() {
		if(document.popup) {
			document.popup.hide();
			document.popup = false;
		}
	});

	$(".droplist a.visibility").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).parent().siblings(".visibility").attr("data-value", $(this).data("value")).attr("title", "shown to " + $(this).text());
		$(this).parent().hide();
		document.popup = false;
		return false;
	});

	$("#contact").submit(function() {
		$("#contact button.save").prop("disabled", true).addClass("working");
		$.post("/user/settings.php?ajax=savecontact", {email: $("#email").val(), vis_email: $("#vis_email").data("value"), website: $("#website").val(), vis_website: $("#vis_website").data("value"), twitter: $("#twitter").val(), vis_twitter: $("#vis_twitter").data("value"), google: $("#google").val(), vis_google: $("#vis_google").data("value"), facebook: $("#facebook").val(), vis_facebook: $("#vis_facebook").data("value"), steam: $("#steam").val(), vis_steam: $("#vis_steam").data("value")}, function(result) {
			$("#contact button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success; update form?
			} else
				alert(result.message);
		}, "json");
	});

	$("#notification").submit(function() {
		$("#notification button.save").prop("disabled", true).addClass("working");
		$.post("/user/settings.php?ajax=savenotification", {notifymsg: $("#notifymsg")[0].checked ? 1 : 0}, function(result) {
			$("#notification button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success; update form?
			} else
				alert(result.message);
		}, "json");
	});

	$("a[href='#removetransition']").click(function() {
		$.post("/user/settings.php?ajax=removetransition", {}, function(result) {
			if(!result.fail)
				window.location.reload(false);
			else
				alert(result.message);
		}, "json");
		return false;
	});
	$("a[href='#removeaccount']").click(function() {
		$.post("/user/settings.php?ajax=removeaccount", {source: $(this).data("source"), id: $(this).data("id")}, function(result) {
			if(!result.fail)
				window.location.reload(false);
			else
				alert(result.message);
		}, "json");
		return false;
	});
});
