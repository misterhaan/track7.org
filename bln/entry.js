$(function() {
  $("#publishentry").click(function() {
    $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("#delentry").remove();
        var nav = $("#publishentry").parent();
        $("#publishentry").remove();
        nav.append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
      } else
        alert(result.message);
    });
    return false;
  });
  $("#delentry").click(function() {
    if(confirm("do you really want to delete this blog entry?  it will be gone forever!"))
      $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail)
          window.location.href = "./";  // to index
        else
          alert(result.message);
      });
    return false;
  });
});
