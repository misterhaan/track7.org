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
		created: function() {
			this.id = $("#editentry").data("entryid");
			this.allTags = [];
			this.originalTags = [];

			this.LoadTagNames();
			if(this.id)
				this.Load();
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.title);
			},
			tagList: {
				get: function() {
					return this.tags.join(",");
				}, set: function(val) {
					this.tags = val ? val.split(",") : [];
				}
			},
			tagChoices: function() {
				if(this.tagSearch) {
					var choices = [];
					if(this.allTags.indexOf(this.tagSearch) < 0 && this.tags.indexOf(this.tagSearch) < 0)
						choices.push("“" + this.tagSearch + "”");
					for(var t = 0; t < this.allTags.length; t++)
						if(this.allTags[t].indexOf(this.tagSearch) >= 0 && this.tags.indexOf(this.allTags[t]) < 0)
							choices.push(this.allTags[t].replace(new RegExp(this.tagSearch.replace(/\./, "\\."), "gi"), "<em>$&</em>"));
					return choices;
				}
				return this.allTags.filter(function(tag) { return entry.tags.indexOf(tag) < 0; });
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
				$.get("/api/tags/names", {type: "blog"}, result => {
					if(!result.fail)
						this.allTags = result.names;
					else
						alert(result.message);
				}, "json");
			},
			Load: function() {
				$.get("/api/blog/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.url = result.url;
						this.title = result.title;
						this.ValidateUrl();
						this.content = result.content;
						this.tagList = result.tags;
						this.originalTags = result.tags ? result.tags.split(",") : [];
						setTimeout(function() { autosize($("#content")); }, 25);
					} else
						alert(result.message);
				});
			},
			Save: function() {
				this.saving = true;
				var data = {id: this.id, title: this.title, url: this.url, content: this.content,
					newtags: $.grep(this.tags, tag => { return $.inArray(tag, this.originalTags) == -1; }).join(","),
					deltags: $.grep(this.originalTags, tag => { return $.inArray(tag, this.tags) == -1; }).join(",")
				};
				$.post("/api/blog/save", data, result => {
					if(!result.fail)
						window.location.href = result.url;
					else
						alert(result.message);
					this.saving = false;
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
				setTimeout(() => {
					this.showTagSuggestions = false;
					this.tagCursor = "";
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
			AddTypedTag: function() {
				if(this.tagSearch && this.tagChoices[0] == "“" + this.tagSearch + "”") {
					this.AddTag(this.tagSearch);
				}
			},
			AddTag: function(name) {
				if(name) {
					this.tagSearch = "";
					this.HideTagSuggestions();
					this.tags.push(name.replace(/<[^>]*>|“|”/gi, ""));
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
});
