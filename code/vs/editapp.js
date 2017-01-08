$(function() {
  LoadApp();
  // TODO:  set url field to verify uniqueness
  $("#name").change(UpdateDefaultURL);
  $("#editapp").submit(SaveApp);
});

function LoadApp() {
  if($("#editapp").data("appid")) {
    $.get("editapp.php", {ajax: "get", id: $("#editapp").data("appid")}, function(result) {
      if(!result.fail) {
        $("#name").val(result.name);
        $("#url").val(result.url);
        $("#desc").val(result.descmd);
        autosize.update($("#desc"));
        $("#github").val(result.github);
        $("#wiki").val(result.wiki);
      } else
        alert(result.message);
    }, "json");
  }
}

function UpdateDefaultURL() {
  $("#url").attr("placeholder", $("#name").val().toLowerCase().replace(/[^a-z0-9\.\-_]*/g, ""));
}

function SaveApp() {
  // TODO:  clear general error message
  $("#save").prop("disabled", true).addClass("working");
  $.post({url: "editapp.php?ajax=save", data: new FormData($("#editapp")[0]), cache: false, contentType: false, processData: false, success: function(result) {
    if(!result.fail)
      window.location.href = result.url;
    else {
      // TODO:  highlight problematic fields and/or show general error message
      $("#save").prop("disabled", false).removeClass("working");
      alert(result.message);
    }
  }, dataType: "json"});
  return false;
}
