$(function() {
  ko.applyBindings(window.LegoViewModel = new LegoViewModel(), $("#editlego")[0]);
  if($("#editlego").data("legoid"))
    window.LegoViewModel.Load();
});

function LegoViewModel() {
  var self = this;

  self.id = ko.observable(false);
  self.title = ko.observable("");
  self.url = ko.observable("");
  self.image = ko.observable(false);
  self.ldraw = false;
  self.instructions = false;
  self.pieces = ko.observable("");
  self.descmd = ko.observable("");

  self.loading = ko.observable(false);

  self.Load = function() {
    self.loading(true);
    $.get("/lego/edit.php", {ajax: "get", id: $("#editlego").data("legoid")}, function(data, status, xhr) {
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
    $.post("/lego/edit.php?ajax=save", {legojson: ko.toJSON(self)}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        window.location.href = result.url;
      else
        alert(result.message);
    });
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

  self.CacheLdraw = function(data, e) {
    var f = e.target.files[0];
    if(f) {
      var fr = new FileReader();
      fr.onloadend = function() {
        self.ldraw = fr.result;
      }
      fr.readAsDataURL(f);
    }
  };

  self.CacheInstructions = function(data, e) {
    var f = e.target.files[0];
    if(f) {
      var fr = new FileReader();
      fr.onloadend = function() {
        self.instructions = fr.result;
      }
      fr.readAsDataURL(f);
    }
  };
}
