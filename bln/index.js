$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  if($("nav.tagcloud").length)
    window.ViewModel.LoadTags();
  window.ViewModel.LoadEntries();
});

function vm() {
  var self = this;
  self.errors = ko.observableArray([]);

  self.tags = ko.observableArray([]);
  self.entries = ko.observableArray([]);

  self.tagid = $("p#taginfo").length ? $("p#taginfo").data("tagid") : false;

  self.loadingEntries = ko.observable(false);
  self.hasMoreEntries = ko.observable(false);

  self.LoadTags = function() {
    self.tags.removeAll();
    $.get('/tags.php', {ajax: "list", type: "blog"}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        for(var t = 0; t < result.tags.length; t++)
          self.tags.push(result.tags[t]);
      else
        self.errors.push(result.message);
    });;
  };

  self.LoadEntries = function() {
    self.hasMoreEntries(false);
    self.loadingEntries(true);
    $.get("/bln/", self.tagid ? {ajax: "entries", tagid: self.tagid, before: self.getOldest()} : {ajax: "entries", before: self.getOldest()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var e = 0; e < result.entries.length; e++)
          self.entries.push(result.entries[e]);
        self.hasMoreEntries(result.hasMore);
      }
      else
        self.errors.push(result.message);
      self.loadingEntries(false);
    });
  };

  self.getOldest = function() {
    if(self.entries().length)
      return self.entries()[self.entries().length - 1].posted.timestamp;
    return false;
  }
}
