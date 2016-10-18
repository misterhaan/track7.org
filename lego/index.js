$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  window.ViewModel.LoadLegos();
});

function vm() {
  var self = this;
  self.errors = ko.observableArray([]);

  self.legos = ko.observableArray([]);

  self.loadingLegos = ko.observable(false);
  self.hasMoreLegos = ko.observable(false);

  self.LoadLegos = function() {
    self.hasMoreLegos(false);
    self.loadingLegos(true);
    $.get("/lego/", {ajax: "legos", before: self.getLast()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var l = 0; l < result.legos.length; l++)
          self.legos.push(result.legos[l]);
        self.hasMoreLegos(result.hasMore);
      }
      else
        self.errors.push(result.message);
      self.loadingLegos(false);
    });
  };

  self.getLast = function() {
    if(self.legos().length)
      return self.legos()[self.legos().length - 1].posted.timestamp;
    return false;
  }
}
