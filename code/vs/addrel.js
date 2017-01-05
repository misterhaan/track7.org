$(function() {
  $("#addrel").submit(SaveRel);
});

function SaveRel() {
  $.post({url: "addrel.php?ajax=save", data: new FormData($("#addrel")[0]), cache: false, contentType: false, processData: false, success: function(result) {
    if(!result.fail)
      window.location.href = result.url;
    else
      alert(result.message);
  }, dataType: "json"});
  return false;
}
