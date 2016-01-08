$(function() {
  $("a[href$='#profile']").click(function() {
    if(!$("#profile").data("loaded"))
      $.get("/user/settings.php?ajax=loadprofile", {}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail) {
          $("#username").val(result.username);
          $("#username").change();
          $("#displayname").val(result.displayname);
          $("#displayname").change();
          $("#profile").data("loaded", true);
        } else
          alert(result.message);
      });
  });

  $("a[href$='#timezone']").click(function() {
    if(!$("#timezone").data("loaded"))
      $.get("/user/settings.php?ajax=loadtime", {}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail) {
          $("#currenttime").val(result.currenttime);
          $("#dst").prop("checked", result.dst);
          $("#timezone").data("loaded", true);
        } else
          alert(result.message);
      });
  });

  $("a[href$='#contact']").click(function() {
    if(!$("#contact").data("loaded"))
      $.get("/user/settings.php?ajax=loadcontact", {}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail) {
          $("#email").val(result.email).change();
          $("#vis_email").val(result.vis_email);
          $("#website").val(result.website).change();
          $("#vis_website").val(result.vis_website);
          $("#twitter").val(result.twitter).change();
          $("#vis_twitter").val(result.vis_twitter);
          $("#google").val(result.google).change();
          $("#vis_google").val(result.vis_google);
          $("#facebook").val(result.facebook).change();
          $("#vis_facebook").val(result.vis_facebook);
          $("#steam").val(result.steam).change();
          $("#vis_steam").val(result.vis_steam);
        } else
          alert(result.message);
      });
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

  $("#username").change(function() {
    var valid = $("#username").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating username...");
    $.get("./?ajax=checkusername", {username: $("#username").val()}, function(data, status, xhr) {
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
      $.get("./?ajax=checkname", {name: $("#displayname").val()}, function(data, status, xhr) {
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

  $("#profile").submit(function() {
    $.post("/user/settings.php?ajax=saveprofile", {username: $("#username").val(), displayname: $("#displayname").val()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("a[href='" + $("#whodat").attr("href") + "']").attr("href", "/user/" + result.username + "/");
        $("#whodat").contents().get(0).nodeValue = result.displayname;
      } else
        alert(result.message);
    });
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
    $.post("/user/settings.php?ajax=savetime", {currenttime: $("#currenttime").val(), dst: $("#dst").prop("checked")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.ressponseText);
      if(!result.fail) {
        // TODO:  indicate success?
      } else
        alert(result.message);
    });
    return false;
  });

  $("#email").change(function() {
    var valid = $("#email").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating e-mail address...");
    if($.trim($("#email").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "e-mail address will be left blank");
    } else
      $.get("./?ajax=checkemail", {email: $("#email").val()}, function(data, status, xhr) {
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
      $.get("?ajax=checkurl", {url: $("#website").val()}, function(data,status,xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          if(result.replace)
            $("#website").val(result.replace);
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "url exists");
        }
      });
  });

  $("#twitter").change(function() {
    var valid = $("#twitter").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating twitter username...");
    if($.trim($("#twitter").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "no twitter profile listed");
    } else
      $.get("?ajax=checktwitter", {twitter: $("#twitter").val()}, function(data,status,xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          if(result.replace)
            $("#twitter").val(result.replace);
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "valid twitter handle");
        }
      });
  });

  $("#google").change(function() {
    var valid = $("#google").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating google+ profile...");
    if($.trim($("#google").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "no google+ profile listed");
    } else
      $.get("?ajax=checkgoogle", {google: $("#google").val()}, function(data,status,xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          if(result.replace)
            $("#google").val(result.replace);
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "valid google+ profile");
        }
      });
  });

  $("#facebook").change(function() {
    var valid = $("#facebook").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating facebook username...");
    if($.trim($("#facebook").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "no facebook profile listed");
    } else
      $.get("?ajax=checkfacebook", {facebook: $("#facebook").val()}, function(data,status,xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          if(result.replace)
            $("#facebook").val(result.replace);
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "valid facebook profile");
        }
      });
  });

  $("#steam").change(function() {
    var valid = $("#steam").parent().siblings(".validation").children("img");
    valid.attr("src", "/images/status/checking.gif");
    valid.parent().attr("title", "validating steam profile...");
    if($.trim($("#steam").val()) == "") {
      valid.attr("src", "/images/status/valid.png");
      valid.parent().attr("title", "no steam profile listed");
    } else
      $.get("?ajax=checksteam", {steam: $("#steam").val()}, function(data,status,xhr) {
        result = $.parseJSON(xhr.responseText);
        if(result.fail) {
          valid.attr("src", "/images/status/invalid.png");
          valid.parent().attr("title", result.message);
        } else {
          if(result.replace)
            $("#steam").val(result.replace);
          valid.attr("src", "/images/status/valid.png");
          valid.parent().attr("title", "valid steam profile");
        }
      });
  });

  $("#contact").submit(function() {
    $.post("/user/settings.php?ajax=savecontact", {email: $("#email").val(), vis_email: $("#vis_email").val(), website: $("#website").val(), vis_website: $("#vis_website").val(), twitter: $("#twitter").val(), vis_twitter: $("#vis_twitter").val(), google: $("#google").val(), vis_google: $("#vis_google").val(), facebook: $("#facebook").val(), vis_facebook: $("#vis_facebook").val(), steam: $("#steam").val(), vis_steam: $("#vis_steam").val()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        // TODO:  indicate success; update form?
      } else
        alert(result.message);
    });
  });

  $("a[href=#removetransition]").click(function() {
    $.post("/user/settings.php?ajax=removetransition", {}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        window.location.reload(false);
      else
        alert(result.message);
    });
    return false;
  });
  $("a[href=#removeaccount]").click(function() {
    $.post("/user/settings.php?ajax=removeaccount", {source: $(this).data("source"), id: $(this).data("id")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        window.location.reload(false);
      else
        alert(result.message);
    });
    return false;
  });
});
