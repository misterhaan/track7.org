$(function() {
  $("#addrel").submit(SaveRel);
});

function SaveRel() {
  $("#save").prop("disabled", true).addClass("working");
  $.post({url: "addrel.php?ajax=save", data: new FormData($("#addrel")[0]), cache: false, contentType: false, processData: false, success: function(result) {
    if(!result.fail)
      window.location.href = result.url;
    else {
      alert(result.message);
      $("#save").prop("disabled", false).removeClass("working");
    }
  }, dataType: "json"});
  return false;
}
