$(function() {
	var editphoto = new Vue({
		el: "#editphoto",
		mixins: [vueMixins.tagSuggest],
		data: {
			id: $("#photoid").val(),
			caption: "",
			url: "",
			youtube: "",
			photo: false,
			storymd: "",
			taken: "",
			year: "",
			loading: true,
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.caption);
			}
		},
		watch: {
			url: function(value) {
				this.url = NameToUrl(value);
			}
		},
		created: function() {
			if(this.id)
				$.get("/api/photos/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.caption = result.caption;
						this.originalUrl = result.url;
						this.url = result.url;
						this.ValidateUrl();
						this.youtube = result.youtube;
						this.storymd = result.storymd;
						setTimeout(function() { autosize.update($("textarea[name='storymd']")); }, 25);
						this.taken = result.taken;
						if(result.year > 0)
							this.year = result.year;
						this.taglist = result.tags;
						this.originalTags = result.tags ? result.tags.split(",") : [];
					} else
						alert(result.message);
				}, "json");
			else {
				this.originalUrl = "";
				this.originalTags = [];
				this.ValidateUrl();
			}
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateField("input[name='url']", "/api/photos/checkurl&id=" + this.id, "url", "validating url...", "url available", "url required");
			},
			CachePhoto: function(e) {
				var f = e.target.files[0];
				if(f) {
					var fr = new FileReader();
					fr.onloadend = function() {
						editphoto.photo = fr.result;
					};
					fr.readAsDataURL(f);
				}
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editphoto")[0]);
				fdata.append("originalurl", this.originalUrl);
				fdata.append("deltags", this.deltags);
				fdata.append("addtags", this.addtags);
				$.post({url: "/api/photos/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
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
