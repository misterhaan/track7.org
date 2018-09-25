$(function() {
	var editwld = new Vue({
		el: "#editwld",
		data: {
			name: "",
			url: "",
			engine: "",
			desc: "",
			dmzx: "",
			released: "",
			screenshot: false,
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.name);
			}
		},
		created: function() {
			this.id = $("#editwld input[name='id']").val();
			if(this.id)
				$.get("/api/gameworlds/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.name = result.name;
						this.url = result.url;
						this.originalUrl = result.url;
						this.ValidateUrl();
						this.engine = result.engine;
						this.desc = result.desc;
						setTimeout(function() { autosize($("textarea")); }, 25);
						this.dmzx = result.dmzx;
						this.released = result.released;
						this.ValidateReleased();
					} else
						alert(result.message);
				}, "json");
			else
				setTimeout(function() { autosize($("textarea")); }, 25);
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/gameworldurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			CacheScreenshot: function(e) {
				var f = e.target.files[0];
				if(f) {
					var fr = new FileReader();
					fr.onloadend = () => {
						this.screenshot = fr.result;
					};
					fr.readAsDataURL(f);
				}
			},
			ValidateReleased: function() {
				ValidateInput("input[name='released']", "/api/validate/pastdatetime", this.id, this.released, "validating date / time...", "valid date / time", {valid: true, message: "will use current date / time"}, newdate => { this.released = newdate; });
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editwld")[0]);
				fdata.set("originalurl", this.originalUrl);
				$.post({url: "/api/gameworlds/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
					if(!result.fail)
						window.location.href = ".#" + result.url;
					else {
						alert(result.message);
						this.saving = false;
					}
				}, dataType: "json"});
			}
		}
	});
});
