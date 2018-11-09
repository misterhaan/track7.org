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

function StartDiscussionViewModel() {
	var self = this;
	this.name = ko.observable("");
	this.contact = ko.observable("");
	this.title = ko.observable("");
	this.tags = ko.observableArray([]);
	this.message = ko.observable("");
	this.saving = ko.observable(false);

	this.Post = function() {
		self.saving(true);
		$.post("?ajax=post", {name: self.name(), contact: self.contact(), title: self.title(), taglist: self.tags().join(","), markdown: self.message()}, function(result) {
			if(result.fail) {
				alert(result.message);
				self.saving(false);
			} else
				window.location.href = result.url;
		});
	};
}
