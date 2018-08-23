$(function() {
	var entry = new Vue({
		el: "#editentry",
		data: {
			title: "",
			url: "",
			content: "",
			tags: [],
			saving: false,
			showTagSuggestions: false,
			tagSearch: "",
			tagCursor: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.title);
			},
			tagList: {
				get: function() {
					return this.tags.join(",");
				}, set: function(val) {
					this.tags = val.split(",");
				}
			},
			tagChoices: function() {
				if(this.tagSearch) {
					var choices = [];
					if(this.allTags.indexOf(this.tagSearch) < 0)
						choices.push("“" + this.tagSearch + "”");
					for(var t = 0; t < this.allTags.length; t++)
						if(this.allTags[t].indexOf(this.tagSearch) >= 0)
							choices.push(this.allTags[t].replace(new RegExp(this.tagSearch.replace(/\./, "\\."), "gi"), "<em>$&</em>"));
					return choices;
				}
				return this.allTags;
			}
		},
		watch: {
			url: function(value) {
				this.url = NameToUrl(value);
			},
			tagSearch: function(value) {
				this.tagSearch = value.toLowerCase().replace(/[^a-z0-9\.]/, "");
			},
			tagCursor: function(value) {
				this.tagCursor = value.replace(/<[^>]>/g, "");
			}
		},
		methods: {
			LoadTagNames: function() {
				$.get("/api/tags/names", {type: "blog"}, function(result) {
					if(!result.fail)
						entry.allTags = result.names;
					else
						alert(result.message);
				}, "json");
			},
			Load: function() {
				$.get("/api/blog/edit", {id: this.id}, function(result) {
					if(!result.fail) {
						entry.url = result.url;
						entry.title = result.title;
						entry.ValidateUrl();
						entry.content = result.content;
						entry.tagList = result.tags;
						entry.originalTags = result.tags.split(",");
						setTimeout(function() { autosize($("#content")); }, 25);
					} else
						alert(result.message);
				});
			},
			Save: function() {
				this.saving = true;
				var data = {id: this.id, title: this.title, url: this.url, content: this.content,
					newtags: $.grep(this.tags, function(e) { return $.inArray(e, entry.originalTags) == -1; }).join(","),
					deltags: $.grep(this.originalTags, function(e) { return $.inArray(e, entry.tags) == -1; }).join(",")
				};
				$.post("/api/blog/save", data, function(result) {
					if(!result.fail)
						window.location.href = result.url;
					else
						alert(result.message);
					entry.saving = false;
				});
			},
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateField("#url", "/api/blog/checkurl&id=" + this.id, "url", "validating url...", "url available", "url required");
			},
			ShowTagSuggestions: function() {
				this.showTagSuggestions = true;
			},
			HideTagSuggestions: function(delay) {
				setTimeout(function() {
					entry.showTagSuggestions = false;
					entry.tagCursor = "";
				}, +delay);
			},
			TagSearchKeyPress: function(e) {
				if(e.which == 8 && this.tagSearch || e.which == 46  // backspace or period
						|| e.which >= 48 && e.which <= 57  // digit
						|| e.which >= 65 && e.which <= 90  // capital letter
						|| e.which >= 97 && e.which <= 122)  // lowercase letter
					this.showTagSuggestions = true;
			},
			FirstTag: function() {
				this.tagCursor = this.tagChoices[0];
				this.showTagSuggestions = true;
			},
			NextTag: function() {
				if(this.tagCursor) {
					for(var t = 0; t < this.tagChoices.length - 1; t++)
						if(this.tagChoices[t].replace(/<[^>]>/g, "") == this.tagCursor) {
							this.tagCursor = this.tagChoices[t + 1];
							this.showTagSuggestions = true;
							return;
						}
				}
				this.FirstTag();
			},
			PrevTag: function() {
				if(this.tagCursor) {
					for(var t = 1; t < this.tagChoices.length; t++)
						if(this.tagChoices[t].replace(/<[^>]>/g, "") == this.tagCursor) {
							this.tagCursor = this.tagChoices[t - 1];
							this.showTagSuggestions = true;
							return;
						}
				}
				this.LastTag();
			},
			LastTag: function() {
				this.tagCursor = this.tagChoices[this.tagChoices.length - 1];
				this.showTagSuggestions = true;
			},
			AddCursorTag: function() {
				if(this.tagCursor && $(".suggestions .selected").length)
					this.AddTag(this.tagCursor);
			},
			AddTag: function(name) {
				if(name) {
					this.tags.push(name.replace(/<[^>]*>|“|”/gi, ""));
					this.tagSearch = "";
					this.HideTagSuggestions();
				}
			},
			DelTag: function(index) {
				this.tags.splice(index, 1);
			},
			DelLastTag: function() {
				if(!this.tagSearch)
					this.tags.splice(-1);
			}
		}
	});
	entry.id = $("#editentry").data("entryid");
	entry.allTags = [];
	entry.originalTags = [];

	entry.LoadTagNames();
	if(entry.id)
		entry.Load();
});
