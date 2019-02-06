$(function() {
	$("a[href$='#profile']").click(function() {
		if(!$("#profile").data("loaded"))
			$.get("/api/settings/profile", {}, result => {
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
			$.get("/api/settings/time", {}, result => {
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
			$.get("/api/settings/contact", {}, result => {
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
					$("#github").val(result.github).change();
					vis = $("#vis_github");
					vis.attr("data-value", result.vis_github);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_github + "']").text());
					$("#deviantart").val(result.deviantart).change();
					vis = $("#vis_deviantart");
					vis.attr("data-value", result.vis_deviantart);
					vis.attr("title", "shown to " + vis.siblings(".droplist").find("a[data-value='" + result.vis_deviantart + "']").text());
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
			$.get("/api/settings/notification", {}, result => {
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

	$("#username").change(function() { ValidateField(this, "/api/settings/checkUsername", "username", "validating username...", "username available."); });
	$("#displayname").change(function() { ValidateField(this, "/api/settings/checkName", "name", "validating display name...", "display name available.", "username will be used for display."); });
	$("#avatarupload").change(function(event) {
		var f = event.target.files[0];
		if(f) {
			var fr = new FileReader();
			fr.onloadend = function() {
				var typechk = fr.result.split(";base64,")[0].split("data:")[1];
				if(typechk == "image/jpeg" || typechk == "image/png" || typechk == "image/jpg") {  // jpg might not actually be used, but here just in case
					var img = $("<img class=avatar>");
					img.on("error", function() {
						alert("error reading uploaded file as image.  avatars should be jpeg or png image files.");
					});
					img.on("load", function() {
						var rad = $("#avatarupload").siblings("input[type='radio']");
						rad.after(img);
						rad.prop("disabled", false);
						rad.prop("checked", true);
						$("#avatarupload").hide();
					});
					img.attr("src", fr.result);
				} else
					alert("only jpeg and png image files can be used as avatars.  your file was recognized as " + typechk);
			}
			fr.readAsDataURL(f);
		}
	});

	$("#profile").submit(function() {
		$("#profile button.save").prop("disabled", true).addClass("working");
		$.post({url: "/api/settings/profile", data: new FormData($("#profile")[0]), cache: false, contentType: false, processData: false, success: result => {
			$("#profile button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				$("a[href='" + $("#whodat").attr("href") + "']").attr("href", "/user/" + result.username + "/");
				$("#whodat").contents().get(0).nodeValue = result.displayname;
				$("#whodat img.avatar").attr("src", result.avatar);
				var filefield = $("#avatarupload");
				filefield.wrap("<form>").closest("form").get(0).reset();
				filefield.unwrap();
				filefield.siblings("img.avatar").remove();
				filefield.show();
				$("input[name='avatar'][value='current']").prop("checked", true).siblings("img").attr("src", result.avatar);
			} else
				alert(result.message);
		}, dataType: "json"});
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
		$.post("/api/settings/time", {currenttime: $("#currenttime").val(), dst: $("#dst").prop("checked")}, result => {
			$("#timezone button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success?
			} else
				alert(result.message);
		}, "json");
		return false;
	});

	$("#email").change(function() { ValidateField(this, "/api/settings/checkEmail", "email", "validating e-mail address...", "looks like an e-mail address.", "e-mail address will be left blank."); });
	$("#website").change(function() { ValidateField(this, "/api/settings/checkUrl", "url", "validating website url...", "url exists.", "no website listed."); });
	$("#twitter").change(function() { ValidateField(this, "/api/settings/checkTwitter", "twitter", "validating twitter username...", "valid twitter handle.", "no twitter profile listed."); });
	$("#google").change(function() { ValidateField(this, "/api/settings/checkGoogle", "google", "validating google+ profile...", "valid google+ profile.", "no google+ profile listed."); });
	$("#facebook").change(function() { ValidateField(this, "/api/settings/checkFacebook", "facebook", "validating facebook username...", "valid facebook profile.", "no facebook profile listed."); });
	$("#github").change(function() { ValidateField(this, "/api/settings/checkGithub", "github", "validating github username...", "valid github profile.", "no github profile listed."); });
	$("#deviantart").change(function() { ValidateField(this, "/api/settings/checkDeviantart", "deviantart", "validating github username...", "valid deviantart profile.", "no deviantart profile listed."); });
	$("#steam").change(function() { ValidateField(this, "/api/settings/checkSteam", "steam", "validating steam profile...", "valid steam profile.", "no steam profile listed."); });

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
		$.post("/api/settings/contact", {email: $("#email").val(), vis_email: $("#vis_email").data("value"), website: $("#website").val(), vis_website: $("#vis_website").data("value"), twitter: $("#twitter").val(), vis_twitter: $("#vis_twitter").data("value"), google: $("#google").val(), vis_google: $("#vis_google").data("value"), facebook: $("#facebook").val(), vis_facebook: $("#vis_facebook").data("value"), github: $("#github").val(), vis_github: $("#vis_github").data("value"), deviantart: $("#deviantart").val(), vis_deviantart: $("#vis_deviantart").data("value"), steam: $("#steam").val(), vis_steam: $("#vis_steam").data("value")}, result => {
			$("#contact button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success; update form?
			} else
				alert(result.message);
		}, "json");
	});

	$("#notification").submit(function() {
		$("#notification button.save").prop("disabled", true).addClass("working");
		$.post("/api/settings/notification", {notifymsg: $("#notifymsg")[0].checked ? 1 : 0}, result => {
			$("#notification button.save").prop("disabled", false).removeClass("working");
			if(!result.fail) {
				// TODO:  indicate success; update form?
			} else
				alert(result.message);
		}, "json");
	});

	$("a[href='#removetransition']").click(function() {
		$.post("/api/settings/removeTransitionalLogin", {}, result => {
			if(!result.fail)
				window.location.reload(false);
			else
				alert(result.message);
		}, "json");
		return false;
	});
	$("a[href='#removeaccount']").click(function() {
		$.post("/api/settings/removeLoginAccount", {source: $(this).data("source"), id: $(this).data("id")}, result => {
			if(!result.fail)
				window.location.reload(false);
			else
				alert(result.message);
		}, "json");
		return false;
	});
});
