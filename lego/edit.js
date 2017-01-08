$(function() {
  ko.applyBindings(window.LegoViewModel = new LegoViewModel(), $("#editlego")[0]);
  if($("#legoid").length)
    window.LegoViewModel.Load();
});

function LegoViewModel() {
  var self = this;

  self.id = ko.observable(false);
  self.title = ko.observable("");
  self.url = ko.observable("");
  self.image = ko.observable(false);
  self.pieces = ko.observable("");
  self.descmd = ko.observable("");

  self.title.subscribe(function() {
    $("#url").attr("placeholder", self.title().toLowerCase().replace(" ", "-"));
  });

  self.loading = ko.observable(false);

  self.Load = function() {
    self.loading(true);
    $.get("/lego/edit.php", {ajax: "get", id: $("#legoid").val()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        self.id(result.id);
        self.title(result.title);
        self.url(result.url);
        self.pieces(result.pieces);
        self.descmd(result.descmd);
        autosize.update($("textarea[data-bind*='descmd']"));
      } else
        alert(result.message);
      self.loading(false);
    });
  };

  self.Save = function() {
    $("#save").prop("disabled", true).addClass("working");
    $.post({url: "/lego/edit.php?ajax=save", data: new FormData($("#editlego")[0]), cache: false, contentType: false, processData: false, success: function(result) {
      if(!result.fail)
        window.location.href = result.url;
      else {
        $("#save").prop("disabled", false).removeClass("working");
        alert(result.message);
      }
    }, dataType: "json"});
  };

  self.CacheImage = function(data, e) {
    var f = e.target.files[0];
    if(f) {
      var fr = new FileReader();
      fr.onloadend = function() {
        self.image(fr.result);
      };
      fr.readAsDataURL(f);
    }
  };
}
