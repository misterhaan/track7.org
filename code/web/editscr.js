$(function() {
	var editscr = new Vue({
		el: "#editscr",
		data: {
			name: "",
			url: "",
			usetype: "",
			desc: "",
			instr: "",
			filelocation: "",
			link: "",
			reqs: [],
			github: "",
			wiki: "",
			released: "",
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.name);
			}
		},
		created: function() {
			this.id = $("#editscr input[name='id']").val();
			if(this.id) {
				$.get("/api/webcode/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.name = result.name;
						this.url = result.url;
						this.originalUrl = result.url;
						this.ValidateUrl();
						this.usetype = result.usetype;
						this.desc = result.desc;
						this.instr = result.instr;
						setTimeout(function() { autosize($("textarea")); }, 25);
						this.filelocation = result.link ? "link" : "upload";
						this.link = result.link;
						this.reqs = result.reqs;
						this.github = result.github;
						this.wiki = result.wiki;
						this.released = result.released;
						this.ValidateReleased();
					} else
						alert(result.message);
				});
			} else
				setTimeout(function() { autosize($("textarea")); }, 25);
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/webcodeurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			ValidateReleased: function() {
				ValidateInput("input[name='released']", "/api/validate/pastdatetime", this.id, this.released, "validating date / time...", "valid date / time", {valid: true, message: "will use current date / time"}, newdate => { this.released = newdate; });
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editscr")[0]);
				fdata.append("originalurl", this.originalUrl);
				$.post({url: "/api/webcode/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
					if(!result.fail)
						window.location.href = result.url;
					else {
						alert(result.message);
						this.saving = false;
					}
				}, dataType: "json"});
			}
		}
	});
});
