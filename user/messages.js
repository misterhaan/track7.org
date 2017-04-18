$(function() {
  if($("#conversations").length) {
    ko.applyBindings(window.Conversations = new ConversationsViewModel(), $("#messages")[0]);
    window.Conversations.Load();
  } else if($("#sendmessage").length)
    ko.applyBindings(window.SendMessage = new SendMessageViewModel(), $("#sendmessage")[0]);
  $("#usermatch").keydown(function(e) {
    var sm = window.SendMessage || window.Conversations;
    if(sm.matchingusers().length && (sm.cursor() && e.which == 13 || e.which == 38 || e.which == 40)) {
      if(sm.cursor())
        if(e.which == 13)
          $("li.highlight").click();
        else if(e.which == 38) {
          for(var u = sm.matchingusers().length - 1; u >= 0; u--)
            if(sm.matchingusers()[u] == sm.cursor()) {
              sm.cursor(u > 0 ? sm.matchingusers()[u - 1] : sm.matchingusers()[sm.matchingusers().length - 1]);
              break;
            }
        } else {
          for(var u = 0; u < sm.matchingusers().length; u++)
            if(sm.matchingusers()[u] == sm.cursor()) {
              sm.cursor(u >= sm.matchingusers().length - 1 ? sm.matchingusers()[0] : sm.matchingusers()[u + 1]);
              break;
            }
        }
      else
        sm.cursor(e.which == 38 ? sm.matchingusers()[sm.matchingusers().length - 1] : sm.matchingusers()[0]);
      e.preventDefault();
    }
  });
  $("#usermatch").blur(function() {
    setTimeout(function() {
      (window.Conversations || window.SendMessage).usermatch("");
    }, 250);
  });
  if(location.hash.substring(0, 5) == "#!to=") {
    $.get("/user/", {ajax: "userinfo", username: location.hash.substring(5)}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail)
        if(window.Conversations)
          window.Conversations.GetConversation(result.user);
        else
          window.SendMessage.Select(result.user);
      else
        (window.Conversations || window.SendMessage).usermatch(location.hash.substring(5));
    });
  }
});

function ConversationsViewModel() {
  var self = this;

  self.usermatch = ko.observable('');
  self.findingusers = ko.observable(false);
  self.matchingusers = ko.observableArray([]);
  self.cursor = ko.observable(false);

  self.conversations = ko.observableArray([]);
  self.selected = ko.observable(false);

  self.loading = ko.observable(false);
  self.error = ko.observable(false);

  self.Load = function() {
    self.loading(true);
    $.get("messages.php", {ajax: "list"}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var c = 0; c < result.conversations.length; c++) {
          result.conversations[c].messages = ko.observableArray([]);
          result.conversations[c].hasmore = ko.observable(false);
          result.conversations[c].loading = ko.observable(false);
          result.conversations[c].error = ko.observable(false);
          result.conversations[c].response = ko.observable('');
          self.conversations.push(result.conversations[c]);
        }
        autosize($("textarea[data-bind*='response']"));
      }
      else
        self.error(result.message);
      self.loading(false);
    });
  };

  self.Select = function(c, reply) {
    self.selected(c);
    if(c.messages().length == 0 && c.id)
      self.LoadMessages(c, reply);
    else if(reply === true)
      $("body").animate({scrollTop: $("form.reply:visible").offset().top}, 750);
    else {
      var id = c.messages()[c.messages().length - 1].id;
      id = $("#pm" + id).is(":hidden") ? "#m" + id : "#pm" + id;
      $("body").animate({scrollTop: $(id).offset().top}, 750);
    }
  }

  self.LoadMessages = function(c, reply) {
    c.hasmore(false);
    if(c.id) {
      c.loading(true);
      $.get("messages.php", {ajax: "messages", conversation: c.id, before: c.messages().length == 0 ? false : c.messages()[0].sent.timestamp}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail) {
          var id = 0;
          if(c.messages().length > 0)
            id = c.messages()[0].id;
          for(var m = 0; m < result.messages.length; m++)
            c.messages.splice(m, 0, result.messages[m]);
          if(result.hasmore)
            c.hasmore(true);
          if(reply === true)
            $("body").animate({scrollTop: $("form.reply:visible").offset().top}, 750);
          else {
            if(id == 0 && c.messages().length > 0)
              id = c.messages()[c.messages().length - 1].id;
            id = $("#pm" + id).is(":hidden") ? "#m" + id : "#pm" + id;
            $("body").animate({scrollTop: $(id).offset().top}, 750);
          }
          Prism.highlightAll();
        } else
          c.error(result.message);
        c.loading(false);
      });
    } else if(reply === true)
      $("body").animate({scrollTop: $("form.reply:visible").offset().top}, 750);
  };

  self.Reply = function(conv) {
    $.post("?ajax=send", {to: conv.thatuser, markdown: conv.response()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        conv.messages.push(result.message);
        conv.response('');
        Prism.highlightAll();
        // TODO:  move conversation to top of list
      } else
        alert(result.message);
    });
  };

  self.usermatch.subscribe(function() {
    if(window.waitUserSuggest)
      clearTimeout(window.waitUserSuggest);
    window.waitUserSuggest = false;
    if(self.usermatch().length < 3) {
      self.matchingusers([]);
      self.findingusers(false);
    } else {
      window.waitUserSuggest = setTimeout(function() {
        self.findingusers(true);
        $.get("/user/", {ajax: "suggest", match: self.usermatch()}, function(data, status, xhr) {
          var result = $.parseJSON(xhr.responseText);
          if(!result.fail) {
            self.matchingusers([]);
            for(var u = 0; u < result.users.length; u++)
              self.matchingusers.push(result.users[u]);
          } else
            alert(result.message);
          self.findingusers(false);
        });
      }, 250);
    }
  });

  self.GetConversation = function(user) {
    // open existing conversation if it exists
    for(var c = 0; c < self.conversations().length; c++)
      if(self.conversations()[c].thatuser == user.id) {
        self.Select(self.conversations()[c], true);
        self.usermatch('');
        return;
      }
    // no existing conversation, so add a new conversation to the top of the list (as it will be the most recent)
    var conv = {
      id: false,
      thatuser: user.id,
      displayname: user.displayname || user.username,
      username: user.username,
      avatar: user.avatar,
      sent: {datetime: "", display: "0 seconds", tooltip: ""},
      issender: 1,
      hasread: 0,
      messages: ko.observableArray([]),
      hasmore: ko.observable(false),
      loading: ko.observable(false),
      error: ko.observable(false),
      response: ko.observable('')
    };
    self.conversations.splice(0, 0, conv);
    self.Select(conv, true);
    autosize($("textarea[data-bind*=response]:visible"));
    self.usermatch('');
  }
}

function SendMessageViewModel() {
  var self = this;

  self.usermatch = ko.observable('');
  self.findingusers = ko.observable(false);
  self.matchingusers = ko.observableArray([]);
  self.cursor = ko.observable(false);
  self.chosenuser = ko.observable(false);

  self.sentmessages = ko.observableArray([]);

  self.usermatch.subscribe(function() {
    if(window.waitUserSuggest)
      clearTimeout(window.waitUserSuggest);
    window.waitUserSuggest = false;
    if(self.usermatch().length < 3) {
      self.matchingusers([]);
      self.findingusers(false);
    } else {
      window.waitUserSuggest = setTimeout(function() {
        self.findingusers(true);
        $.get("/user/", {ajax: "suggest", match: self.usermatch()}, function(data, status, xhr) {
          var result = $.parseJSON(xhr.responseText);
          if(!result.fail) {
            self.matchingusers([]);
            for(var u = 0; u < result.users.length; u++)
              self.matchingusers.push(result.users[u]);
          } else
            alert(result.message);
          self.findingusers(false);
        });
      }, 250);
    }
  });

  self.Select = function(user) {
    self.chosenuser(user);
    self.usermatch('');
    $("#fromname").focus();
  };

  self.Clear = function() {
    self.chosenuser(false);
    $("#usermatch").focus();
  };

  self.Send = function() {
    $.post("?ajax=send", {to: self.chosenuser().id, fromname: $("#fromname").val(), fromcontact: $("#fromcontact").val(), markdown: $("#markdown").val()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        // TODO:  show message and clear form probably
        self.sentmessages.push(result.message);
        self.chosenuser(false);
        $("#fromname").val("");
        $("#fromcontact").val("");
        $("#markdown").val("");
      } else
        alert(result.message);
    });
  };
}
