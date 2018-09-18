$(function() {
	var addrel = new Vue({
		el: "#addrel",
		data: {
			version: "",
			released: "",
			saving: false
		},
		created: function() {
			this.id = $("#addrel input[name='app']").val();
		},
		methods: {
			ValidateVersion: function() {
				ValidateInput("input[name='version']", "/api/applications/validateversion", this.id, this.version, "checking if version is already released...", "can release this version", "version is required");
			},
			ValidateReleased: function() {
				ValidateInput("input[name='released']", "/api/validate/pastdatetime", this.id, this.released, "validating date / time...", "valid date / time", {valid: true, message: "will use current date / time"}, newdate => { this.released = newdate; });
			},
			Save: function() {
				this.saving = true;
				$.post({url: "/api/applications/addrelease", data: new FormData($("#addrel")[0]), cache: false, contentType: false, processData: false, success: result => {
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
