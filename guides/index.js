$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  if($("nav.tagcloud").length)
    window.ViewModel.LoadTags();
  window.ViewModel.LoadGuides();
  $("#editdesc").hide();
  $("a[href$='#tagedit']").click(function(e) {
    $("#editdesc textarea").val($("#taginfo .editable").html());
    $("#editdesc").show().focus();
    $("a[href$='#tagedit']").hide();
    e.preventDefault();
  });
  $("a[href$='#save']").click(function(e) {
    $.post("/tags.php?ajax=setdesc&type=guide", {id: $("#taginfo").data("tagid"), description: $("#editdesc textarea").val()}, function(data, status, xhr) {
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
  self.guides = ko.observableArray([]);

  self.tagid = $("p#taginfo").length ? $("p#taginfo").data("tagid") : false;

  self.loadingGuides = ko.observable(false);
  self.hasMoreGuides = ko.observable(false);

  self.LoadTags = function() {
    self.tags.removeAll();
    $.get('/tags.php', {ajax: "list", type: "guide"}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        for(var t = 0; t < result.tags.length; t++)
          self.tags.push(result.tags[t]);
      else
        self.errors.push(result.message);
    });
  };

  self.LoadGuides = function() {
    self.hasMoreGuides(false);
    self.loadingGuides(true);
    $.get("/guides/", self.tagid ? {ajax: "guides", tagid: self.tagid, before: self.getOldest()} : {ajax: "guides", before: self.getOldest()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var g = 0; g < result.guides.length; g++)
          self.guides.push(result.guides[g]);
        self.hasMoreGuides(result.hasMore);
      }
      else
        self.errors.push(result.message);
      self.loadingGuides(false);
    });
  };

  self.getOldest = function() {
    if(self.guides().length)
      return self.guides()[self.guides().length - 1].posted.timestamp;
    return false;
  }
}
