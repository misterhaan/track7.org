$(function() {
  $("#username").change(function() {
    var valid = $("#username").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating username...");
    $.get("../?ajax=checkusername", {username: $("#username").val()}, function(data, status, xhr) {
      result = $.parseJSON(xhr.responseText);
      if(result.fail) {
        valid.attr("src", "/images/status/invalid.png");
        valid.parent().attr("title", result.message);
      } else {
        valid.attr("src", "/images/status/valid.png");
        valid.parent().attr("title", "username available");
      }
    });
  });

  $("#displayname").change(function() {
    var valid = $("#displayname").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating display name...");
    if($.trim($("#displayname").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "username will be used for display");
    } else
      $.get("../?ajax=checkname", {name: $("#displayname").val()}, function(data, status, xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "display name available");
        }
      });
  });

  $("#email").change(function() {
    var valid = $("#email").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating e-mail address...");
    if($.trim($("#email").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "e-mail address will be left blank");
    } else
      $.get("../?ajax=checkemail", {email: $("#email").val()}, function(data, status, xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "looks like an e-mail address");
        }
      });
  });

  $("#website").change(function() {
    var valid = $("#website").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating website url...");
    if($.trim($("#website").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "no website listed");
    } else
      $.get("../settings.php?ajax=checkurl", {url: $("#website").val()}, function(data, status, xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "url exists");
        }
      });
  });

  $("#newuser").submit(function() {
    $.post("../?ajax=register", {csrf: $("#csrf").val(), username: $("#username").val(), displayname: $("#displayname").val(), email: $("#email").val(), website: $("#website").val(), linkprofile: $("#linkprofile").val(), useavatar: $("#useavatar").val()}, function(data, status, xhr) {
      result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        if(result.continue)
          location.replace(result.continue);
        else
          location.replace("/user/settings.php");
      else
        alert(result.message);
    });
    return false;
  });

  $("#username").change();
  $("#displayname").change();
  $("#email").change();
  $("#website").change();
});
