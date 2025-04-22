$(function() {
	InitUserMenu();
	InitLoginLogout();
	autosize($("textarea"));
	InitTabLayout();
});

/**
 * initialize the user menu or login menu
 */
function InitUserMenu() {
	document.popup = false;
	$("#signin, #whodat").click(function() {
		var menu = $("#usermenu, #loginmenu");
		if(document.popup && document.popup[0] == menu[0]) {
			menu.hide();
			document.popup = false;
		} else {
			if(document.popup)
				document.popup.hide();
			document.popup = menu;
			document.popup.show();
		}
		return false;
	});
	$("#usermenu, #loginmenu").click(function(e) {
		e.stopPropagation();
	});
	$(document).click(function(event) {
		if(document.popup) {
			document.popup.hide();
			document.popup = false;
		}
	});
}

/**
 * initialize the login form or the logout link
 */
function InitLoginLogout() {
	if($("#signinform").length) {
		$("input[type=radio][name=login_url]").change(UpdateLoginType);
		$("#signinform").submit(Login);
		UpdateLoginType();
	}
	$("#logoutlink").click(() => {
		$.post("/api/user.php/logout").done(() => {
			window.location.reload(false);
		}).fail(request => {
			alert(request.responseText);
		});
		return false;
	});
}

/**
 * handle login provider selection
 */
function UpdateLoginType() {
	$("input[type=radio][name=login_url]").parent().removeClass("selected");
	var sel = $("input[type=radio][name=login_url]:checked");
	var button = $("#dologin");
	button.prop("disabled", sel.length == 0);
	sel.parent().addClass("selected");
	if(sel.length) {
		var logintype = sel.parent().attr("class").split(" ")[0];
		if(logintype == "track7") {
			$("#oldlogin").show();
			button.html("sign in with track7 password");
		} else {
			$("#oldlogin").hide();
			button.html("sign in with " + logintype);
		}
	} else {
		button.html("choose site to sign in through");
		$("#oldlogin").hide();
	}
}

/**
 * perform a login
 */
function Login() {
	var sel = $("input[type=radio][name=login_url]:checked");
	if(sel.length) {
		var url = sel.val();
		if(url.indexOf("/") == 0) {
			$.post("/api/user.php/login/" + $("#rememberlogin").is(":checked"), { username: $("input[name=username]").val(), password: $("input[name=password]").val() }).done(() => {
				window.location.reload();
			}).fail(request => {
				alert(request.responseText);
			});
		} else {
			if(!$("#rememberlogin").is(":checked"))
				url = url.replace("remember%26", "");
			window.location = url;
		}
	}
	return false;
}

/**
 * initialize tabbed layout
 */
function InitTabLayout() {
	$(".tabs a").click(function() {
		var hash = $(this).prop("hash");
		$(".tabcontent:visible").hide();
		$(".tabs a.selected").removeClass("selected");
		$(hash).show();
		$(this).addClass("selected");
		if(hash != location.hash)
			if(history.replaceState)
				history.replaceState(null, null, hash);
			else
				location.hash = hash;
		return false;
	});
}

/**
 * Validate a form field via ajax.  Usually called when the field changes.
 * @param field form field to validate
 * @param url ajax url to request (HTTP GET) for validation
 * @param name parameter name for sending field value to server
 * @param msgchk tooltip message while the field is being validated
 * @param msgok tooltip message when the field successfully validated
 * @param msgblank tooltip message when the field is blank (blank value will be sent to )
 */
function ValidateField(field, url, name, msgchk, msgok, msgblank) {
	field = $(field);
	var valid = field.parent().siblings(".validation");
	valid.removeClass().addClass("validation").addClass("checking");
	valid.attr("title", msgchk);
	var value = $.trim(field.val()) || $.trim(field.attr("placeholder"));
	if(msgblank && value == "") {
		valid.removeClass("checking").addClass("valid");
		valid.attr("title", msgblank);
	} else
		$.get(url, { [name]: value }, function(result) {
			valid.removeClass("checking");
			if(result.fail) {
				valid.addClass("invalid");
				valid.attr("title", result.message);
			} else {
				valid.addClass("valid");
				valid.attr("title", msgok);
			}
		}, "json");
}

/**
 * validate an input via ajax.  usually called when the field changes.
 * @param input form field to validate.  used as a jquery selector.
 * @param ajaxurl url for the validation ajax request.
 * @param id id of the item the input belongs to.  only used for uniqueness validations.
 * @param value value to validate.
 * @param msgchk message to display while waiting for validation.
 * @param msgok message to display when validation is successful.
 * @param msgblank message to display when value is blank.  pass an object with .valid=true and message in .message otherwise blank is considered invalid.
 * @param changevalue function for changing the value if validation says it should change.
 */
function ValidateInput(input, ajaxurl, id, value, msgchk, msgok, msgblank, changevalue) {
	input = $(input);
	var valid = input.parent().siblings(".validation");
	if(!valid.length)
		valid = $("<span class=validation></span>").appendTo(input.parent().parent());
	valid.removeClass().addClass("validation").addClass("checking");
	valid.attr("title", msgchk);
	if(msgblank && value == "") {
		valid.removeClass("checking").addClass(msgblank.message ? (msgblank.valid ? "valid" : "invalid") : "invalid");
		valid.attr("title", msgblank.message || msgblank);
	} else
		$.get(ajaxurl, { id: id, value: value }, result => {
			valid.removeClass("checking");
			if(!result.fail) {
				valid.addClass("valid");
				valid.attr("title", result.message || msgok);
				if(result.newvalue && changevalue)
					changevalue(result.newvalue);
			} else {
				valid.addClass("invalid");
				valid.attr("title", result.message);
			}
		}, "json");
}
