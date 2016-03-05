$(function() {
  $("a[href$='#pull']").click(function() {
    $.post("?ajax=pull", {}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $(".actions").after("<pre><code>" + result.output + "</code></pre>");
        var now = new Date();
        var hour = now.getHours();
        $(".actions").after("<h2>git pull returned " + result.retcode + " at "
            + (hour == 0 ? 12 : hour > 12 ? hour - 12 : hour)
            + (now.getMinutes() < 10 ? ":0" : ":") + now.getMinutes()
            + (hour >= 12 ? " pm" : " am") + "</h2>");
      } else
        alert(result.message);
    });
    return false;
  });
});