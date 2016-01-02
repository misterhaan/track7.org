$(function() {
  ko.applyBindings(window.ViewModel = new vm());
  window.ViewModel.LoadComments();
  $("#addcomment").submit(function() {
    var formdata = {md: $("#newcomment").val(), type: $(this).data("type"), key: $(this).data("key")};
    if($("#authorname").length)
      $.extend(formdata, {name: $("#authorname").val(), contact: $("#authorcontact").val()});
    $.post("/comments.php?ajax=add", formdata, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        window.ViewModel.AddComment(result);
        $("#newcomment").val("");
      }
      else
        alert(result.message);
    });
    return false;
  });
  $("#publishentry").click(function() {
    $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        $("#delentry").remove();
        var nav = $("#publishentry").parent();
        $("#publishentry").remove();
        nav.append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
      } else
        alert(result.message);
    });
    return false;
  });
  $("#delentry").click(function() {
    if(confirm("do you really want to delete this blog entry?  it will be gone forever!"))
      $.post(this.href, {id: $(this).parent().data("id")}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail)
          window.location.href = "./";  // to index
        else
          alert(result.message);
      });
    return false;
  });
});

function vm() {
  var self = this;
  self.comments = ko.observableArray([]);

  self.loadingComments = ko.observable(false);
  self.hasMoreComments = ko.observable(false);
  self.error = ko.observable(false);

  self.AddComment = function(comment) {
    comment.html = ko.observable(comment.html);
    if(!comment.editing)
      comment.editing = ko.observable(false);
    if(!comment.markdown)
      comment.markdown = ko.observable("");
    self.comments.push(comment);
  }

  self.LoadComments = function() {
    self.hasMoreComments(false);
    self.loadingComments(true);
    var pathparts = window.location.pathname.split("/");
    $.get("/comments.php", {ajax: "get", type: $("#addcomment").data("type"), key: $("#addcomment").data("key")}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        for(var e = 0; e < result.comments.length; e++)
          self.AddComment(result.comments[e]);
        //self.hasMoreComments(result.hasMore);
      } else
        self.error(result.message);
      self.loadingComments(false);
    });
  };

  self.EditComment = function(comment) {
    $.post("/comments.php?ajax=edit", {type: $("#addcomment").data("type"), id: comment.id}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        comment.markdown(result.markdown);
        comment.editing(true);
      } else
        alert(result.message);
    });
    return false;
  }

  self.SaveComment = function(comment) {
    $.post("/comments.php?ajax=save", {type: $("#addcomment").data("type"), id: comment.id, markdown: comment.markdown()}, function(data, status, xhr) {
      var result = $.parseJSON(xhr.responseText);
      if(!result.fail) {
        comment.html(result.html);
        comment.editing(false);
      } else
        alert(result.message);
    });
    return false;
  }

  self.UneditComment = function(comment) {
    comment.editing(false);
    return false;
  }

  self.DeleteComment = function(comment) {
    if(confirm("do you really want to delete your comment?  you won’t be able to get it back."))
      $.post("/comments.php?ajax=delete", {type: $("#addcomment").data("type"), id: comment.id}, function(data, status, xhr) {
        var result = $.parseJSON(xhr.responseText);
        if(!result.fail)
          self.comments.remove(comment);
        else
          alert(result.message);
      });
    return false;
  };
}
