$(function() {
  ko.applyBindings(window.ArtViewModel = new ArtViewModel(), $("#editart")[0]);
  if($("#artid").length)
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
    $.get("/art/edit.php", {ajax: "get", id: $("#artid").val()}, function(result) {
      if(!result.fail) {
        self.id(result.id);
        self.title(result.title);
        self.url(result.url);
        self.ext(result.ext);
        self.descmd(result.descmd);
        autosize.update($("textarea[name='descmd']"));
        self.taglist(result.tags);
        self.originalTaglist = result.tags;
      } else
        alert(result.message);
      self.loading(false);
    }, "json");
  };

  self.title.subscribe(function() {
    $("#url").attr("placeholder", self.title().split(" ").join("-"));
  });

  self.Save = function() {
    $("#save").prop("disabled", true).addClass("working");
    var data = new FormData($("#editart")[0]);
    data.append("originalTaglist", self.originalTaglist.join(","));
    $.post({url: "/art/edit.php?ajax=save", data: data, cache: false, contentType: false, processData: false, success: function(result) {
      if(!result.fail)
        window.location.href = result.url;
      else {
        $("#save").prop("disabled", false).removeClass("working");
        alert(result.message);
      }
    }, dataType: "json"});
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
