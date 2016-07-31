$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  if($("nav.tagcloud").length)
    window.ViewModel.LoadTags();
  window.ViewModel.LoadPhotos();
  $("#editdesc").hide();
  $("a[href$='#tagedit']").click(function(e) {
    $("#editdesc textarea").val($("#taginfo .editable").html());
    $("#editdesc").show().focus();
    $("a[href$='#tagedit']").hide();
    e.preventDefault();
  });
  $("a[href$='#save']").click(function(e) {
    $.post("/tags.php?ajax=setdesc&type=photos", {id: $("#taginfo").data("tagid"), description: $("#editdesc textarea").val()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("#taginfo .editable").html($("#editdesc textarea").val());
        $("a[href$='#tagedit']").show();
        $("#editdesc").hide();
      } else
        alert(result.message);
    });
    e.preventDefault();
  });
  $("a[href$='#cancel']").click(function(e) {
    $("a[href$='#tagedit']").show();
    $("#editdesc").hide();
    e.preventDefault();
  });
});

function vm() {
  var self = this;
  self.errors = ko.observableArray([]);

  self.tags = ko.observableArray([]);
  self.photos = ko.observableArray([]);

  self.tagid = $("#taginfo").length ? $("#taginfo").data("tagid") : false;

  self.loadingPhotos = ko.observable(false);
  self.hasMorePhotos = ko.observable(false);

  self.LoadTags = function() {
    self.tags.removeAll();
    $.get('/tags.php', {ajax: "list", type: "photos"}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        for(var t = 0; t < result.tags.length; t++)
          self.tags.push(result.tags[t]);
      else
        self.errors.push(result.message);
    });
  };

  self.LoadPhotos = function() {
    self.hasMorePhotos(false);
    self.loadingPhotos(true);
    $.get("/album/", self.tagid ? {ajax: "photos", tagid: self.tagid, before: self.getOldest()} : {ajax: "photos", before: self.getOldest()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var p = 0; p < result.photos.length; p++)
          self.photos.push(result.photos[p]);
        self.hasMorePhotos(result.hasMore);
      }
      else
        self.errors.push(result.message);
      self.loadingPhotos(false);
    });
  };

  self.getOldest = function() {
    if(self.photos().length)
      return self.photos()[self.photos().length - 1].posted.timestamp;
    return false;
  }
}
