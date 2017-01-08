$(function() {
  ko.applyBindings(window.PhotoViewModel = new PhotoViewModel(), $("#editphoto")[0]);
  if($("#photoid").length)
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
    $.get("/album/edit.php", {ajax: "get", id: $("#photoid").val()}, function(result) {
      if(!result.fail) {
        self.id(result.id);
        self.caption(result.caption);
        self.url(result.url);
        self.youtube(result.youtube);
        self.storymd(result.storymd);
        autosize.update($("textarea[name='storymd']"));
        self.taken(result.taken);
        if(result.year > 0)
          self.year(result.year);
        self.taglist(result.tags);
        self.originalTaglist = result.tags;
      } else
        alert(result.message);
      self.loading(false);
    }, "json");
  };

  self.caption.subscribe(function() {
    $("#url").attr("placeholder", self.caption().split(" ").join("-"));
  });

  self.Save = function() {
    $("#save").prop("disabled", true).addClass("working");
    var fdata = new FormData($("#editphoto")[0]);
    fdata.append("originalTaglist", self.originalTaglist.join(","));
    $.post({url: "/album/edit.php?ajax=save", data: fdata, cache: false, contentType: false, processData: false, success: function(result) {
      if(!result.fail)
        window.location.href = result.url;
      else {
        $("#save").prop("disabled", false).removeClass("working");
        alert(result.message);
      }
    }, dataType: "json"});
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
  };
}
