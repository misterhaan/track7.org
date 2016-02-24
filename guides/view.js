$(function() {
  $("a[href$='/edit.php?ajax=publish']").click(function() {
    $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("a[href$='/edit.php?ajax=delete']").remove();
        var nav = $("a[href$='/edit.php?ajax=publish']").parent();
        $("a[href$='/edit.php?ajax=publish']").remove();
        nav.append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
      } else
        alert(result.message);
    });
    return false;
  });
  $("a[href$='/edit.php?ajax=delete']").click(function() {
    if(confirm("do you really want to delete this guide?  it will be gone forever!"))
      $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail)
          window.location.href = "../";  // to index
        else
          alert(result.message);
      });
    return false;
  });
});
