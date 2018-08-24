$(function() {
	InitUserMenu();
	InitLoginLogout();
	autosize($("textarea"));
	InitTags();
	InitVoting();
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
 * initialize tag cloud or tag description edit if present
 */
function InitTags() {
	// tag cloud init for pages with vue
	if(typeof Vue == "function" && $(".tagcloud[data-tagtype]").length) {
		var tagcloud = $(".tagcloud");
		tagcloud.removeClass("hidden");
		var tagdata = new Vue({
			el: ".tagcloud",
			data: {
				tags: []
			}
		});
		$.get("/api/tags/list", {type: tagcloud.data("tagtype")}, function(result) {
			if(result.fail)
				alert(result.message);
			else
				tagdata.tags = result.tags;
		}, "json");
	}

	// tag description edit (does nothing if not present on page) 
	$("#editdesc").hide();
	$("#editdesc a[href$='#cancel']").click(function(e) {
		$("a[href$='#tagedit']").show();
		$("#editdesc").hide();
		e.preventDefault();
	});
	$("a[href$='#tagedit']").click(function(e) {
		$("a[href$='#tagedit']").hide();
		$("#editdesc").show();
		$("#editdesc textarea").val($("#taginfo .editable").html()).focus();
		e.preventDefault();
	});
	$("#editdesc a[href$='#save']").click(function(e) {
		$.post("/api/tags/setdesc", {type: $("#taginfo").data("tagtype"), id: $("#taginfo").data("tagid"), description: $("#editdesc textarea").val()}, function(result) {
			if(!result.fail) {
				$("#taginfo .editable").html($("#editdesc textarea").val());
				$("a[href$='#tagedit']").show();
				$("#editdesc").hide();
			} else
				alert(result.message);
		});
		e.preventDefault();
	});
}

/**
 * initialize voting
 */
function InitVoting() {
	if($("#vote").length) {
		$("#vote, #vote span").click(function() {
			// TODO:  move to api
			$.post("/votes.php?ajax=cast", {type: $("#vote").data("type"), key: $("#vote").data("key"), vote: $(this).data("vote")}, function(result) {
				if(!result.fail) {
					$("#vote, #vote span").removeClass("voted");
					$("#vote").addClass("voted");
					$("#vote span").each(function() {
						if(+$(this).data("vote") <= result.vote)
							$(this).addClass("voted");
					});
					if(result.rating)
						$("span.rating").attr("data-stars", Math.round(2*result.rating)/2);
					if(result.rating && result.votes)
						$("span.rating").attr("title", "rated " + result.rating + " stars by " + (result.votes == 1 ? "1 person" : result.votes + " people"));
				} else
					alert(result.message);
			}, "json");
			return false;
		});
	}
}

/**
 * initialize comment section and load comments
 * @returns
 */
function InitComments() {
	if($("#comments").length) {
		var comments;
		if(typeof Vue === "function") {
			// TODO:  load comments with vue
			comments = new Vue({
				el: "#comments",
				data: {
					comments: [],
					oldest: "",
					loading: false,
					hasMore: false,
					error: ""
				},
				methods: {
					AddComment: function(comment) {
						comment.editing = false;
						this.comments.push(comment);
					},
					Load: function() {
						this.loading = true;
						if($("h1").data("user"))
							;  // TODO:  load comment page comments
						else
							$.get("/api/comments/keyed", {type: $("#addcomment").data("type"), key: $("#addcomment").data("key"), oldest: this.oldest}, function(result) {
								if(!result.fail) {
									comments.comments = comments.comments.concat(result.comments);
									comments.oldest = result.oldest;
									comments.hasMore = result.hasMore;
									Prism.highlightAll();
								} else
									comments.error = result.message;
								comments.loading = false;
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
						$.post("/api/comments/save", {type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id, markdown: comment.markdown}, function(result) {
							if(!result.fail) {
								comment.html = result.html;
								comment.editing = false;
								Prism.highlightAll();
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
							$.post("/api/comments/delete", {type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id}, function(result) {
								if(!result.fail)
									comments.comments.splice(index, 1);
								else
									alert(result.message);
							}, "json");
					}
				}
			});
			comments.Load();
		} else if(typeof ko === "object") {
			ko.applyBindings(comments = new CommentsViewModel(), $("#comments")[0]);
			comments.LoadComments();
		}
		$("#addcomment").submit(function() {
			var formdata = {md: $("#newcomment").val(), type: $(this).data("type"), key: $(this).data("key")};
			if($("#authorname").length)
				$.extend(formdata, {name: $("#authorname").val(), contact: $("#authorcontact").val()});
			$.post("/api/comments/add", formdata, function(result) {
				if(!result.fail) {
					comments.AddComment(result);
					$("#newcomment").val("");
				} else
					alert(result.message);
			});
			return false;
		});
	}
}

/**
 * view model for comments on the page.  automatically loaded if knockout is
 * available and an element with the id "comments" exists.
 */
function CommentsViewModel() {
	var self = this;

	self.comments = ko.observableArray([]);
	self.oldest = "";

	self.loadingComments = ko.observable(false);
	self.hasMoreComments = ko.observable(false);
	self.error = ko.observable(false);

	self.AddComment = function(comment) {
		comment.editing = ko.observable(false);
		comment.markdown = ko.observable(comment.markdown);
		comment.html = ko.observable(comment.html);
		self.comments.push(comment);
	}

	self.LoadComments = function() {
		self.loadingComments(true);
		if($("h1").data("user"))
			$.get("?ajax=getall", {userid: $("h1").data("user"), oldest: self.oldest}, function(result) {
				if(result.fail)
					alert(result.message);
				else {
					for(var c = 0; c < result.comments.length; c++) {
						self.AddComment(result.comments[c]);
					}
					self.oldest = result.oldest;
					self.hasMoreComments(result.more);
					Prism.highlightAll();
				}
				self.loadingComments(false);
			}, "json");
		else
			$.get("/comments.php", {ajax: "get", type: $("#addcomment").data("type"), key: $("#addcomment").data("key"), oldest: self.oldest}, function(result) {
				if(!result.fail) {
					for(var e = 0; e < result.comments.length; e++)
						self.AddComment(result.comments[e]);
					self.oldest = result.oldest;
					self.hasMoreComments(result.more);
					Prism.highlightAll();
				} else
					self.error(result.message);
				self.loadingComments(false);
			}, "json");
	};

	self.EditComment = function(comment) {
		comment.editing(true);
		comment.savedmarkdown = comment.markdown();
		$("textarea[data-bind*='markdown']:visible").each(function() {
			var ta = $(this);
			if(ta.data("asinit"))
				autosize.update(ta);
			else {
				autosize(ta);
				ta.data("asinit", true);
			}
		});
		return false;
	}

	self.SaveComment = function(comment) {
		$.post("/comments.php?ajax=save", {type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id, markdown: comment.markdown()}, function(result) {
			if(!result.fail) {
				comment.html(result.html);
				comment.editing(false);
				Prism.highlightAll();
			} else
				alert(result.message);
		});
		return false;
	}

	self.UneditComment = function(comment) {
		comment.editing(false);
		comment.markdown(comment.savedmarkdown);
		delete comment.savedmarkdown;
		return false;
	}

	self.DeleteComment = function(comment) {
		if(confirm("do you really want to delete your comment?  you won’t be able to get it back."))
			$.post("/comments.php?ajax=delete", {type: comment.srctbl ? comment.srctbl.replace("_comments", "") : $("#addcomment").data("type"), id: comment.id}, function(result) {
				if(!result.fail)
					self.comments.remove(comment);
				else
					alert(result.message);
			});
		return false;
	};
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
		$.get(url, {[name]: value}, function(result) {
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

if(typeof Vue == "function") {
	Vue.config.keyCodes.comma = 188;
}
