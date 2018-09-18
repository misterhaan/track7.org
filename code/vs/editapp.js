$(function() {
	var editapp = new Vue({
		el: "#editapp",
		data: {
			name: "",
			url: "",
			desc: "",
			icon: false,
			github: "",
			wiki: "",
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.name);
			}
		},
		created: function() {
			this.id = $("#appid").val();
			if(this.id)
				$.get("/api/applications/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.name = result.name;
						this.url = result.url;
						this.ValidateUrl();
						this.originalUrl = result.url;
						this.desc = result.descmd;
						setTimeout(function() { autosize($("textarea[name='desc']")); }, 25);
						this.github = result.github;
						this.wiki = result.wiki;
					} else
						alert(result.message);
				}, "json");
			else
				setTimeout(function() { autosize($("textarea[name='desc']")); }, 25);
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/applicationurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			CacheIcon: function(e) {
				var f = e.target.files[0];
				if(f) {
					var fr = new FileReader();
					fr.onloadend = () => {
						this.icon = fr.result;
					};
					fr.readAsDataURL(f);
				}
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editapp")[0]);
				fdata.append("originalurl", this.originalUrl);
				$.post({url: "/api/applications/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
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
