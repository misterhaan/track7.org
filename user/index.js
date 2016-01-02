$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  window.ViewModel.GetUsers();
});

function vm() {
  var self = this;
  self.users = ko.observableArray([]);
  self.loadingUsers = ko.observable(false);
  self.hasMoreUsers = ko.observable(false);
  
  self.GetUsers = function() {
    self.hasMoreUsers(false);
    self.loadingUsers(true);
    $.get("/user/", {ajax: "listusers"}, function(data, status, xhr){
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var u = 0; u < result.users.length; u++)
          self.users.push(result.users[u]);
        self.hasMoreUsers(result.hasMore);
      }
      self.loadingUsers(false);
    });
  }
}
