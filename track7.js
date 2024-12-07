$(function() {
	InitUserMenu();
	InitLoginLogout();
	autosize($("textarea"));
	InitComments();
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
	$("#logoutlink").click(function() {
		$.post("/", { logout: true }, function() {
			window.location.reload(false);
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

/**
 * initialize comment section and load comments
 * @returns
 */
function InitComments() {
	if($("#comments").length && typeof Vue === "function") {
		var comments = new Vue({
			el: "#comments",
			data: {
				comments: [],
				loading: false,
				hasMore: false,
				error: ""
			},
			created: function() {
				this.Load();
			},
			methods: {
				AddComment: function() {
					var formdata = { md: $("#newcomment").val(), type: $("#addcomment").data("type"), key: $("#addcomment").data("key") };
					if($("#authorname").length)
						$.extend(formdata, { name: $("#authorname").val(), contact: $("#authorcontact").val() });
					$.post("/api/comments/add", formdata, result => {
						if(!result.fail) {
							result.editing = false;
							this.comments.push(result);
							$("#newcomment").val("");
						} else
							alert(result.message);
					});
				},
				Load: function() {
					this.loading = true;
					var userid = $("h1").data("user");
					if(userid == "all") {
						var endpoint = "all";
						var data = {};
					} else if(userid) {
						endpoint = "user";
						data = { userid: userid };
					} else {
						endpoint = "keyed";
						data = { type: $("#addcomment").data("type"), key: $("#addcomment").data("key") };
					}
					data.oldest = this.oldest;
					$.get("/api/comments/" + endpoint, data, result => {
						if(!result.fail) {
							this.comments = this.comments.concat(result.comments);
							this.oldest = result.oldest;
							this.hasMore = result.more;
							setTimeout(() => { Prism.highlightAll(); }, 50);
						} else
							this.error = result.message;
						this.loading = false;
					}, "json");
				},
				Edit: function(comment) {
					comment.editing = true;
					comment.savemarkdown = comment.markdown;
					setTimeout(function() {
						$(".content.edit textarea:visible").each(function() {
							var ta = $(this);
							if(ta.data("asinit"))
								autosize.update(ta);
							else {
								autosize(ta);
								ta.data("asinit", true);
							}
						});
					}, 25);
				},
				Save: function(comment) {
					$.post("/api/comments/save", { type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id, markdown: comment.markdown }, function(result) {
						if(!result.fail) {
							comment.html = result.html;
							comment.editing = false;
							setTimeout(() => { Prism.highlightAll(); }, 50);
						} else
							alert(result.message);
					}, "json");
				},
				Unedit: function(comment) {
					comment.editing = false;
					comment.markdown = comment.savemarkdown;
					delete comment.savemarkdown;
				},
				Delete: function(comment, index) {
					if(confirm("do you really want to delete your comment?  you won’t be able to get it back."))
						$.post("/api/comments/delete", { type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id }, function(result) {
							if(!result.fail)
								comments.comments.splice(index, 1);
							else
								alert(result.message);
						}, "json");
				}
			}
		});
	}
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
 * Translate a name into a URL segment based on the name.
 * @param name display name or title
 * @returns URL segment
 */
function NameToUrl(name) {
	return name.toLowerCase().replace(/ /g, "-").replace(/[^a-z0-9\.\-_]*/g, "");
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
