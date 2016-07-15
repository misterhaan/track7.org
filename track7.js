$(function() {
  // user menu
  document.popup = false;
  $("#signin, #whodat").click(function() {
    if(document.popup) {
      document.popup.hide();
      document.popup = false;
    } else {
      document.popup = $("#usermenu, #loginmenu");
      document.popup.show();
    }
    return false;
  });
  $(document).click(function(event) {
    var target = event.target;
    while(target) {
      if(target.id == "usermenu" || target.id == "loginmenu")
        return;
      target = target.parentNode;
    }
    if(document.popup) {
      document.popup.hide();
      document.popup = false;
    }
  });

  // login form
  if($("#signinform").length) {
    $("input[type=radio][name=login_url]").change(UpdateLoginType);
    $("#signinform").submit(Login);
    UpdateLoginType();
  }

  $("#logoutlink").click(function() {
    $.post("/bln/", { logout: true }, function() {
      window.location.reload(false);
    });
    return false;
  });

  // textareas
  autosize($("textarea"));

  // voting
  if($("#vote").length) {
    $("#vote, #vote span").click(function() {
      $.post("/votes.php?ajax=cast", {type: $("#vote").data("type"), key: $("#vote").data("key"), vote: $(this).data("vote")}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
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
      });
      return false;
    });
  }

  // comments
  if(typeof ko === 'object' && $("#comments").length) {
    ko.applyBindings(window.Comments = new CommentsViewModel(), $("#comments")[0]);
    window.Comments.LoadComments();
    $("#addcomment").submit(function() {
      var formdata = {md: $("#newcomment").val(), type: $(this).data("type"), key: $(this).data("key")};
      if($("#authorname").length)
        $.extend(formdata, {name: $("#authorname").val(), contact: $("#authorcontact").val()});
      $.post("/comments.php?ajax=add", formdata, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail) {
          window.Comments.AddComment(result);
          $("#newcomment").val("");
        }
        else
          alert(result.message);
      });
      return false;
    });
  }

  // tabbed layout
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
});

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

function UpdateLoginType() {
  $("input[type=radio][name=login_url]").parent().removeClass("selected");
  var sel = $("input[type=radio][name=login_url]:checked");
  var button = $("#dologin");
  button.prop("disabled", sel.length == 0);
  sel.parent().addClass("selected");
  if(sel.length) {
    var logintype = sel.siblings("img").attr("alt");
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
 * view model for comments on the page.  automatically loaded if knockout is
 * available and an element with the id "comments" exists.
 */
function CommentsViewModel() {
  var self = this;

  self.comments = ko.observableArray([]);

  self.loadingComments = ko.observable(false);
  self.hasMoreComments = ko.observable(false);
  self.error = ko.observable(false);

  self.AddComment = function(comment) {
    comment.html = ko.observable(comment.html);
    if(!comment.editing)
      comment.editing = ko.observable(false);
    if(!comment.markdown)
      comment.markdown = ko.observable("");
    self.comments.push(comment);
  }

  self.LoadComments = function() {
    self.hasMoreComments(false);
    self.loadingComments(true);
    var pathparts = window.location.pathname.split("/");
    $.get("/comments.php", {ajax: "get", type: $("#addcomment").data("type"), key: $("#addcomment").data("key")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var e = 0; e < result.comments.length; e++)
          self.AddComment(result.comments[e]);
        //self.hasMoreComments(result.hasMore);
      } else
        self.error(result.message);
      self.loadingComments(false);
    });
  };

  self.EditComment = function(comment) {
    $.post("/comments.php?ajax=edit", {type: $("#addcomment").data("type"), id: comment.id}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        comment.markdown(result.markdown);
        comment.editing(true);
        var ta = $("textarea[data-bind*='markdown']:visible");
        if(ta.data("asinit"))
          autosize.update(ta);
        else {
          autosize(ta);
          ta.data("asinit", true);
        }
      } else
        alert(result.message);
    });
    return false;
  }

  self.SaveComment = function(comment) {
    $.post("/comments.php?ajax=save", {type: $("#addcomment").data("type"), id: comment.id, markdown: comment.markdown()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        comment.html(result.html);
        comment.editing(false);
      } else
        alert(result.message);
    });
    return false;
  }

  self.UneditComment = function(comment) {
    comment.editing(false);
    return false;
  }

  self.DeleteComment = function(comment) {
    if(confirm("do you really want to delete your comment?  you won’t be able to get it back."))
      $.post("/comments.php?ajax=delete", {type: $("#addcomment").data("type"), id: comment.id}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail)
          self.comments.remove(comment);
        else
          alert(result.message);
      });
    return false;
  };
}
