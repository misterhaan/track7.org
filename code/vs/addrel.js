$(function() {
  $("#binfile").change(CacheFile);
  $("#bin32file").change(CacheFile);
  $("#srcfile").change(CacheFile);
  $("#addrel").submit(SaveRel);
});

function CacheFile() {
  var fld = this;
  fld.reading = true;
  var fr = new FileReader();
  fr.onloadend = function() {
    fld.cachedFile = this.result;
    fld.reading = false;
  }
  fr.readAsDataURL(fld.files[0]);
  fld.ext = fld.files[0].name.split('.').pop();
}

function SaveRel() {
  // TODO:  clear general error message
  var postdata = { app: $("#addrel").data("appid"), version: $("#version").val(), released: $("#released").val(), language: $("#language").val(), dotnet: $("#dotnet").val(), studio: $("#studio").val() };
  if($("#binurl").val())
    postdata.binurl = $("#binurl").val();
  else {
    postdata.binfile = $("#binfile")[0].cachedFile;
    postdata.binext = $("#binfile")[0].ext;
  }
  if($("#bin32url").val())
    postdata.bin32url = $("#bin32url").val();
  else if($("#bin32file").val()) {
    postdata.bin32file = $("#bin32file")[0].cachedFile;
    postdata.bin32ext = $("#bin32file")[0].ext;
  }
  if($("#srcurl").val())
    postdata.srcurl = $("#srcurl").val();
  else {
    postdata.srcfile = $("#srcfile")[0].cachedFile;
    postdata.srcext = $("#srcfile")[0].ext;
  }
  $.post("addrel.php?ajax=save", postdata, function(data, status, xhr) {
    var result = $.parseJSON(xhr.responseText);
    if(!result.fail)
      window.location.href = result.url;
    else {
      // TODO:  highlight problematic fields or show general error message
      window.alert(result.message);
    }
  });
  return false;
}
