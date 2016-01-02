$(function() {
  $("a.addfriend").click(friend);
  $("a.removefriend").click(friend);
});

function friend() {
  var link = this;
  $.get(link.href, {}, function(data, status, xhr) {
    var result = $.parseJSON(xhr.responseText);
    if(!result.fail) {
      if(link.className == "addfriend") {
        link.className = "removefriend";
        link.href = link.href.replace("add", "remove");
        $(link).text("remove friend");
        link.title = "remove " + link.title.substring(4, link.title.length - 12) + " from your friends";
      } else {
        link.className = "addfriend";
        link.href = link.href.replace("remove", "add");
        $(link).text("add friend");
        link.title = "add " + link.title.substring(7, link.title.length - 18) + " as a friend";
      }
    } else
      alert(result.message);
  });
  return false;
}