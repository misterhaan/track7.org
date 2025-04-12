import "jquery";

document.popup = [];
InitUserMenu();
InitLoginLogout();

/**
 * initialize the user menu or login menu
 */
function InitUserMenu() {
	$("#signin, #whodat").click(event => {
		const menu = $("#usermenu, #loginmenu");
		if(document.popup.length && document.popup[0][0] == menu[0]) {
			document.popup.shift().hide();
		} else {
			if(document.popup.length)
				document.popup[0].hide();
			document.popup.unshift(menu);
			document.popup[0].show();
		}
		event.preventDefault();
		event.stopPropagation();
	});
	$("#usermenu, #loginmenu").click(event => {
		event.stopPropagation();  // so document click doesn't close the menu
	});
	$(document).click(() => {
		if(document.popup.length)
			document.popup.shift().hide();
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
	const sel = $("input[type=radio][name=login_url]:checked");
	const button = $("#dologin");
	button.prop("disabled", sel.length == 0);
	sel.parent().addClass("selected");
	if(sel.length) {
		const logintype = sel.parent().attr("class").split(" ")[0];
		if(logintype == "track7") {
			$("#oldlogin").show();
			button.html("sign in with track7 password");
		} else {
			$("#oldlogin").hide();
			button.html("sign in with " + logintype);
		}
	} else {
		button.html("choose site to sign in through");
	}
}

/**
 * perform a login
 */
function Login() {
	const sel = $("input[type=radio][name=login_url]:checked");
	if(sel.length) {
		const url = sel.val();
		if(url.indexOf("/") == 0) {
			$("input[name=login_url]").prop("disabled", true);
			$("#signinform").attr("action", url).attr("method", "post");
		} else {
			if(!$("#rememberlogin").is(":checked"))
				url = url.replace("remember%26", "");
			window.location = url;
			return false;
		}
	}
}
