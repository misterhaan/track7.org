$(function() {
	var editlego = new Vue({
		el: "#editlego",
		data: {
			id: false,
			title: "",
			url: "",
			image: false,
			pieces: "",
			descmd: "",
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
			this.id = $("#legoid").val();
			if(this.id) {
				$.get("/api/legos/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.title = result.title;
						this.url = result.url;
						this.originalUrl = result.url;
						this.ValidateUrl();
						this.pieces = result.pieces;
						this.descmd = result.descmd;
						setTimeout(function() { autosize($("textarea[name='descmd']")); }, 25);
					} else
						alert(result.message);
				}, "json");
			} else {
				this.originalUrl = "";
				this.ValidateUrl();
				setTimeout(function() { autosize($("textarea[name='descmd']")); }, 25);
			}
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/legourl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			CacheImage: function(e) {
				var f = e.target.files[0];
				if(f) {
					var fr = new FileReader();
					fr.onloadend = () => {
						this.image = fr.result;
					};
					fr.readAsDataURL(f);
				}
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editlego")[0]);
				fdata.append("originalurl", this.originalUrl);
				$.post({url: "/api/legos/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
					if(!result.fail)
						window.location.href = result.url;
					else {
						this.saving = false;
						alert(result.message);
					}
				}, dataType: "json"});
				this.saving = false;
			}
		}
	});
});
