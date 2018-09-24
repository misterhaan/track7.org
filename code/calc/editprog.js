$(function() {
	var editprog = new Vue({
		el: "#editprog",
		data: {
			name: "",
			url: "",
			subject: "",
			model: "",
			desc: "",
			ticalc: "",
			released: "",
			saving: false
		},
		computed: {
			defaultUrl: function() {
				return NameToUrl(this.name);
			},
			ticalcPrefix: function() {
				var prefix = this.ticalc.toString().slice(0, -2);
				return prefix ? prefix + "/" : "";
			}
		},
		created: function() {
			this.id = $("#editprog input[name='id']").val();
			if(this.id)
				$.get("/api/calcprog/edit", {id: this.id}, result => {
					if(!result.fail) {
						this.name = result.name;
						this.url = result.url;
						this.originalUrl = result.url;
						this.ValidateUrl();
						this.subject = result.subject;
						this.model = result.model;
						this.desc = result.desc;
						setTimeout(function() { autosize($("textarea")); }, 25);
						this.ticalc = result.ticalc;
						this.released = result.released;
						this.ValidateReleased();
					} else
						alert(result.message);
				});
			else
				setTimeout(function() { autosize($("textarea")); }, 25);
		},
		methods: {
			ValidateDefaultUrl: function() {
				if(!this.url)
					this.ValidateUrl();
			},
			ValidateUrl: function() {
				ValidateInput("input[name='url']", "/api/validate/calcurl", this.id, this.url || this.defaultUrl, "validating url...", "url available", "url required");
			},
			ValidateReleased: function() {
				ValidateInput("input[name='released']", "/api/validate/pastdatetime", this.id, this.released, "validating date / time...", "valid date / time", {valid: true, message: "will use current date / time"}, newdate => { this.released = newdate; });
			},
			Save: function() {
				this.saving = true;
				var fdata = new FormData($("#editprog")[0]);
				fdata.append("originalurl", this.originalUrl);
				$.post({url: "/api/calcprog/save", data: fdata, cache: false, contentType: false, processData: false, success: result => {
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
