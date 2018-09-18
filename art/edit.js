$(function() {
	var editart = new Vue({
		el: "#editart",
		mixins: [vueMixins.tagSuggest],
		data: {
			id: $("#artid").val(),
			title: "",
			url: "",
			art: false,
			descmd: "",
			deviation: "",
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.title);
			}
		},
		created: function() {
			if(this.id)
				$.get("/api/art/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.title = result.title;
						this.url = result.url;
						this.ValidateUrl();
						this.originalUrl = result.url;
						this.ext = result.ext;
						this.descmd = result.descmd;
						setTimeout(function() { autosize($("textarea[name='descmd']")); }, 25);
						this.deviation = result.deviation;
						this.taglist = result.tags;
						this.originalTags = result.tags ? result.tags.split(",") : [];
					} else
						alert(result.message);
				}, "json");
			else {
				this.originalUrl = "";
				this.originalTags = [];
				setTimeout(function() { autosize($("textarea[name='descmd']")); }, 25);
			}
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/arturl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			CacheArt: function(e) {
				var f = e.target.files[0];
				if(f) {
					var fr = new FileReader();
					fr.onloadend = () => {
						this.art = fr.result;
					};
					fr.readAsDataURL(f);
				}
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editart")[0]);
				fdata.append("originalurl", this.originalUrl);
				fdata.append("deltags", this.deltags);
				fdata.append("addtags", this.addtags);
				$.post({url: "/api/art/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
					if(!result.fail)
						window.location.href = result.url;
					else
						alert(result.message);
					this.saving = false;
				}, dataType: "json"});
			}
		}
	});
});
