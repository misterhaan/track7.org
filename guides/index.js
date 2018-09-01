$(function() {
	var guides = new Vue({
		el: "#guides",
		data: {
			hasTag: $("#taginfo").length > 0,
			guides: [],
			hasMore: false,
			loading: true,
			error: ""
		},
		created: function() {
			this.Load();
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/guides/list", {tagid: $("p#taginfo").data("tagid"), before: this.oldest}, result => {
					if(!result.fail) {
						this.guides = this.guides.concat(result.guides);
						this.hasMore = result.hasMore;
						this.oldest = result.oldest;
					} else
						this.error = result.message;
					this.loading = false;
				});
			}
		}
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
