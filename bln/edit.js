$(function() {
  window.originalTags = [];
  LoadEntry();
  // TODO:  fill blank url field based on title field
  // TODO:  set url field to only accept allowed characters
  // TODO:  set url field to verify uniqueness
  // TODO:  set tags field to only accept allowed characters
  // TODO:  set up tags field to suggest existing tags
  $("#editentry").submit(SaveEntry);
});

function LoadEntry() {
  if($("#editentry").data("entryid")) {
    $.get("edit.php", {ajax: "get", id: $("#editentry").data("entryid")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("#title").val(result.title);
        $("#url").val(result.url);
        $("#content").val(result.content);
        $("#tags").val(result.tags.join(","));
        window.originalTags = result.tags;
      } else
        alert(result.message);
    });
  }
}

function SaveEntry() {
  var tags = $("#tags").val().split(",");
  // TODO:  clear general error message
  $.post("edit.php?ajax=save", { id: $("#editentry").data("entryid"), title: $("#title").val(),
    url: $("#url").val(), content: $("#content").val(),
    newtags: $.grep(tags, function(el) { return $.inArray(el, window.originalTags) == -1; }).join(","),
    deltags: $.grep(window.originalTags, function(el) { return $.inArray(el, tags) == -1; }).join(",")}, function(data, status, xhr) {
    var result = $.parseJSON(xhr.responseText);
    if(!result.fail)
      window.location.href = result.url;
    else {
      // TODO:  highlight problematic fields or show general error message
    }
  });
  return false;
}
