$(function() {
	var main = new Vue({
		el: "main",
		data: {
			num: 0,
			instant: {
				display: "",
				timestamp: 0
			},
			fullentry: "",
			version: -1,
			name: "",
			email: "",
			website: "",
			comment: "",
			results: []
		},
		watch: {
			num: function(num) {
				$("#nextentry").val(+num + 1);
			}
		},
		created: function() {
			this.Load(1);
		},
		methods: {
			Load: function(num) {
				$.get("?ajax=entry", {id: num || +$("#nextentry").val()}, result => {
					if(result.fail)
						alert(result.message);
					else if(result.entry) {
						this.num = result.entry.id;
						this.instant = result.entry.instant;
						this.fullentry = result.entry.comments;
						this.version = result.entry.version;
						this.name = result.entry.name;
						this.email = result.entry.email;
						this.website = result.entry.website;
						this.comment = result.entry.comment;
					} else
						alert("no more entries i guess.");
				}, "json");
			},
			Skip: function() {
				this.results.unshift("skipped entry #" + this.num);
				this.Load();
			},
			SaveMessage: function() {
				var from = $("input[name='msgfrom']:checked").val();
				var username = $("input[name='msgusername']").val();
				var email = this.email;
				if(email)
					email = "mailto:" + email;
				$.post("?ajax=savemessage", {name: this.name, from: from, email: email, website: this.website, username: username, comment: this.comment, instant: this.instant.timestamp}, result => {
					if(!result.fail) {
						this.results.unshift("saved entry #" + this.num + " as a message to misterhaan");
						this.Load();
					} else
						alert(result.message);
				}, "json");
			},
			SaveComment: function() {
				var from = $("input[name='commentfrom']:checked").val();
				var username = $("input[name='commentusername']").val();
				var email = this.email;
				if(email)
					email = "mailto:" + email;
				$.post("?ajax=saveartcomment", {arturl: $("#arturl").val(), name: this.name, from: from, email: email, website: this.website, username: username, comment: this.comment, instant: this.instant.timestamp}, result => {
					if(!result.fail) {
						this.results.unshift("saved entry #" + this.num + " as a comment on " + $("#arturl").val());
						this.Load();
					} else
						alert(result.message);
				}, "json");
			}
		}
	});
});
