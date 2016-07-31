$(function() {
  ko.applyBindings(window.PhotoViewModel = new PhotoViewModel(), $("#editphoto")[0]);
  if($("#editphoto").data("photoid"))
    window.PhotoViewModel.Load();
});

function PhotoViewModel() {
  var self = this;

  self.id = ko.observable(false);
  self.caption = ko.observable("");
  self.url = ko.observable("");
  self.youtube = ko.observable("");
  self.photo = ko.observable(false);
  self.storymd = ko.observable("");
  self.taken = ko.observable("");
  self.year = ko.observable("");
  self.taglist = ko.observableArray([]);
  self.tags = ko.pureComputed({
    read: function() { return self.taglist().join(","); },
    write: function(value) { self.taglist(value.split(",")); }
  });
  self.originalTaglist = [];

  self.loading = ko.observable(false);

  self.Load = function() {
    self.loading(true);
    $.get("/album/edit.php", {ajax: "get", id: $("#editphoto").data("photoid")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        self.id(result.id);
        self.caption(result.caption);
        self.url(result.url);
        self.youtube(result.youtube);
        self.storymd(result.storymd);
        autosize.update($("textarea[data-bind*='storymd']"));
        self.taken(result.taken);
        if(result.year > 0)
          self.year(result.year);
        self.taglist(result.tags);
        self.originalTaglist = result.tags;
      } else
        alert(result.message);
      self.loading(false);
    });
  };

  self.Save = function() {
    $.post("/album/edit.php?ajax=save", {photojson: ko.toJSON(self)}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        window.location.href = result.url;
      else
        alert(result.message);
    });
  };

  self.CachePhoto = function(data, e) {
    var f = e.target.files[0];
    if(f) {
      var fr = new FileReader();
      fr.onloadend = function() {
        self.photo(fr.result);
      };
      fr.readAsDataURL(f);
    }
  }
}
