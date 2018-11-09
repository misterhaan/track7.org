$(function() {
	var editdiscussion = new Vue({
		el: "#editdiscussion",
		data: {
			name: "",
			contact: "",
			title: "",
			tags: [],
			message: "",
			saving: false
		},
		computed: {
			hasRequiredFields: function() {
				return this.title.trim() && this.tags.length && this.message.trim();
			}
		},
		created: function() {
			setTimeout(() => {
				autosize($("textarea[name='message']"));
			}, 25);
		},
		methods: {
			Save: function() {
				this.saving = true;
				$.post("/api/forum/start", $("#editdiscussion").serializeArray(), result => {
					if(!result.fail)
						window.location.href = result.url;
					else {
						alert(result.message);
						this.saving = false;
					}
				}, "json");
			}
		}
	});
});
