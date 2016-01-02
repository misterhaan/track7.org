$(function() {
  // user menu
  document.popup = false;
  $("#signin, #whodat").click(function() {
    if(document.popup) {
      document.popup.hide();
      document.popup = false;
    } else {
      document.popup = $("#usermenu");
      document.popup.show();
    }
    return false;
  });
  $(document).click(function(event) {
    var target = event.target;
    while(target) {
      if(target.id == "usermenu")
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
