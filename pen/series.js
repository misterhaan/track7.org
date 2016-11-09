$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  window.ViewModel.LoadStories();
});

function vm() {
  var self = this;
  self.errors = ko.observableArray([]);

  self.stories = ko.observableArray([]);

  self.loadingStories = ko.observable(false);

  self.LoadStories = function() {
    self.loadingStories(true);
    $.get("/pen/series.php", {ajax: "stories", series: $("h1").data("series-id")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        for(var s = 0; s < result.stories.length; s++)
          self.stories.push(result.stories[s]);
      else
        self.errors.push(result.message);
      self.loadingStories(false);
    });
  };
}
