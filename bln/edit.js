$(function() {
	var entry = new Vue({
		el: "#editentry",
		mixins: [vueMixins.tagSuggest],
		data: {
			title: "",
			url: "",
			content: "",
			tags: [],
			saving: false,
		},
		created: function() {
			this.id = $("#editentry").data("entryid");
			if(this.id)
				$.get("/api/blog/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.url = result.url;
						this.title = result.title;
						this.ValidateUrl();
						this.content = result.content;
						this.taglist = result.tags;
						this.originalTags = result.tags ? result.tags.split(",") : [];
						setTimeout(function() { autosize($("#content")); }, 25);
					} else
						alert(result.message);
				});
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.title);
			},
		},
		watch: {
			url: function(value) {
				this.url = NameToUrl(value);
			},
		},
		methods: {
			Save: function() {
				this.saving = true;
				var data = {id: this.id, title: this.title, url: this.url, content: this.content, addtags: this.addtags, deltags: this.deltags};
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
				ValidateInput("#url", "/api/validate/blogurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", {valid: false, message: "url required"});
			},
		}
	});
});
