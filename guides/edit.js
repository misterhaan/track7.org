$(function() {
	var editguide = new Vue({
		el: "#editguide",
		mixins: [vueMixins.tagSuggest],
		data: {
			status: "draft",
			title: "",
			url: "",
			summary: "",
			level: "intermediate",
			pages: [{
				heading: "",
				markdown: ""
			}],
			correctionsOnly: false,
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.title);
			}
		},
		watch: {
			url: function(value) {
				this.url = NameToUrl(value);
			}
		},
		created: function() {
			this.id = $("#editguide").data("id");
			this.deletedPageIds = [];
			if(this.id)
				$.get("/api/guides/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.status = result.status;
						this.title = result.title;
						this.url = result.url;
						this.ValidateUrl();
						this.summary = result.summary;
						this.level = result.level;
						this.taglist = result.tags;
						this.originalTags = result.tags ? result.tags.split(",") : [];
						this.pages = result.pages;
						setTimeout(function() { autosize($("textarea")); }, 25);
					} else
						alert(result.message);
				}, "json");
			else
				this.originalTags = [];
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("#url", "/api/validate/guideurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", {valid: false, message: "url required"});
			},
			MovePageUp: function(page) {
				var index = this.pages.indexOf(page);
				if(index > 0) {
					this.pages.splice(index, 1);
					this.pages.splice(index - 1, 0, page);
				}
			},
			MovePageDown: function(page) {
				var index = this.pages.indexOf(page);
				if(index >= 0 && index < this.pages.length - 1) {
					this.pages.splice(index, 1);
					this.pages.splice(index + 1, 0, page);
				}
			},
			RemovePage: function(page) {
				if(confirm("do you really want to remove this page?  any changes to its content will be lost.")) {
					var index = this.pages.indexOf(page);
					if(index >= 0) {
						this.pages.splice(index, 1);
						if(page.id)
							this.deletedPageIds.push(page.id);
					}
				}
			},
			AddPage: function() {
				this.pages.push({heading: "", markdown: ""});
				autosize($("textarea").last());
			},
			Save: function() {
				this.saving = true;
				var data = {
					status: this.status,
					title: this.title,
					url: this.url,
					summary: this.summary,
					level: this.level,
					deltags: this.deltags,
					addtags: this.addtags,
					pages: this.pages,
					deletedPageIds: this.deletedPageIds,
					correctionsOnly: this.correctionsOnly
				};
				$.post("/api/guides/save", {id: this.id, guidejson: JSON.stringify(data)}, result => {
					if(!result.fail)
						window.location.href = result.url;
					else
						alert(result.message);
					this.saving = false;
				}, "json");
			}
		}
	});
});
