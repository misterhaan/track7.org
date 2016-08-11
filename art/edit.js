$(function() {
  ko.applyBindings(window.ArtViewModel = new ArtViewModel(), $("#editart")[0]);
  if($("#editart").data("artid"))
    window.ArtViewModel.Load();
});

function ArtViewModel() {
  var self = this;

  self.id = ko.observable(false);
  self.title = ko.observable("");
  self.url = ko.observable("");
  self.ext = ko.observable("");
  self.art = ko.observable(false);
  self.descmd = ko.observable("");
  self.taglist = ko.observableArray([]);
  self.tags = ko.pureComputed({
    read: function() { return self.taglist().join(","); },
    write: function(value) { self.taglist(value.split(",")); }
  });
  self.originalTaglist = [];

  self.loading = ko.observable(false);

  self.Load = function() {
    self.loading(true);
    $.get("/art/edit.php", {ajax: "get", id: $("#editart").data("artid")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        self.id(result.id);
        self.title(result.title);
        self.url(result.url);
        self.ext(result.ext);
        self.descmd(result.descmd);
        autosize.update($("textarea[data-bind*='descmd']"));
        self.taglist(result.tags);
        self.originalTaglist = result.tags;
      } else
        alert(result.message);
      self.loading(false);
    });
  };

  self.Save = function() {
    $.post("/art/edit.php?ajax=save", {artjson: ko.toJSON(self)}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        window.location.href = result.url;
      else
        alert(result.message);
    });
  };

  self.CacheArt = function(data, e) {
    var f = e.target.files[0];
    if(f) {
      var fr = new FileReader();
      fr.onloadend = function() {
        self.art(fr.result);
      };
      fr.readAsDataURL(f);
    }
  };
}
