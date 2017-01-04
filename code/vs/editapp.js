$(function() {
  LoadApp();
  // TODO:  fill blank url field based on title field
  // TODO:  set url field to only accept allowed characters
  // TODO:  set url field to verify uniqueness
  $("#icon")[0].cachedFile = false;
  $("#icon").change(CacheFile);
  $("#editapp").submit(SaveApp);
});

function LoadApp() {
  if($("#editapp").data("appid")) {
    $.get("editapp.php", {ajax: "get", id: $("#editapp").data("appid")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("#name").val(result.name);
        $("#url").val(result.url);
        $("#desc").val(result.descmd);
        autosize.update($("#desc"));
        $("#github").val(result.github);
        $("#wiki").val(result.wiki);
      } else
        alert(result.message);
    });
  }
}

function CacheFile() {
  var fld = this;
  var fr = new FileReader();
  fr.onloadend = function() {
    fld.cachedFile = this.result;
  };
  fr.readAsDataURL(fld.files[0]);
}

function SaveApp() {
  // TODO:  clear general error message
  $.post("editapp.php?ajax=save", { id: $("#editapp").data("appid"), name: $("#name").val(), url: $("#url").val(), desc: $("#desc").val(), icon: $("#icon")[0].cachedFile, github: $("#github").val(), wiki: $("#wiki").val()}, function(data, status, xhr) {
    var result = $.parseJSON(xhr.responseText);
    if(!result.fail)
      window.location.href = result.url;
    else {
      // TODO:  highlight problematic fields or show general error message
    }
  });
  return false;
}
