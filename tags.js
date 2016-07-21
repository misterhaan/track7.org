$(function() {
  ko.applyBindings(window.Tags = new TagsViewModel(), $("#taginfo")[0]);
  $("nav.tabs a").click(function() {
    window.Tags.Load($(this).attr("href").substring(1));
  });
});

function TagsViewModel() {
  var self = this;

  self.type = ko.observable("");
  self.prefix = ko.pureComputed(function() {
    switch(self.type()) {
      case "blog":
        return "showing blog entries";
      case "guide":
        return "showing guides dealing with";
      default:
        return "";
    }
  });
  self.postfix = ko.pureComputed(function() {
    switch(self.type()) {
      case "blog":
        return "go back to all entries.";
      case "guide":
        return "go back to all guides.";
      default:
        return "";
    }
  });

  self.tags = ko.observableArray([]);

  self.descriptionedit = ko.observable(false);

  self.loading = ko.observable(false);
  self.error = ko.observable(false);

  self.MakeUrl = function(name) {
    switch(self.type()) {
      case "blog":
        return "/bln/" + name + "/";
      case "guide":
        return "/guides/" + name + "/";
      default:
        return "";
    }
  };

  self.Load = function(type) {
    self.type(type);
    self.loading(true);
    self.tags([]);
    $.get("tags.php", {ajax: "list", type: type, full: 1}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        for(var t = 0; t < result.tags.length; t++) {
          result.tags[t].description = ko.observable(result.tags[t].description);
          result.tags[t].editing = ko.observable(false);
          self.tags.push(result.tags[t]);
        }
      else
        self.error(result.message);
      self.loading(false);
    });
  };

  self.Edit = function(tag) {
    tag.editing(true);
    self.descriptionedit(tag.description());
  }

  self.Cancel = function(tag) {
    self.descriptionedit(false);
    tag.editing(false);
  }

  self.Save = function(tag) {
    $.post("?ajax=setdesc&type=" + self.type(), {id: tag.id, description: self.descriptionedit()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        tag.description(self.descriptionedit());
        self.descriptionedit(false);
        tag.editing(false);
      } else
        alert(result.message);
    });
  }
}
