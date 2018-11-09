$(function() {
	var main = new Vue({
		el: "main",
		data: {
			replies: [],
			loading: false,
			more: false
		},
		created: function() {
			this.userid = $("h1").data("user");
			this.Load(); 
		},
		methods: {
			Load: function() {
				this.loading = true;
				$.get("/api/forum/replies", {before: this.latest, userid: this.userid}, result => {
					this.loading = false;
					if(!result.fail) {
						this.replies = this.replies.concat(result.replies.map(r => {r.editing = false; r.edits = r.edits || []; return r;}));
						this.more = result.more;
						this.latest = result.latest;
						setTimeout(() => {
							Prism.highlightAll();
						}, 25);
					} else
						alert(result.message);
				}, "json");
			},
			Edit: function(reply) {
				reply.savedmarkdown = reply.markdown;
				reply.editing = true;
				setTimeout(() => {
					var ta = $("#r" + reply.id + " textarea");
					autosize(ta);
					$("html, body").animate({scrolltop: ta.offset().top}, 750);
					ta.focus();
				}, 25);
			},
			Unedit: function(reply) {
				reply.editing = false;
				reply.markdown = reply.savedmarkdown;
				delete reply.savedmarkdown;
			},
			Save: function(reply, stealth) {
				$.post("/api/forum/update", {reply: reply.id, markdown: reply.markdown, stealth: stealth}, result => {
					if(!result.fail) {
						reply.html = result.html;
						if(result.edit)
							reply.edits.push(result.edit)
						reply.editing = false;
						delete reply.savemarkdown;
						setTimeout(() => { Prism.highlightAll(); }, 25);
					} else
						alert(result.message);
				}, "json");
			},
			Delete: function(reply) {
				if(confirm("deleted replies cannot be recovered.  are you sure you want to delete?"))
					$.post("/api/forum/delete", {id: reply.id}, result => {
						if(!result.fail)
							this.replies.splice(this.replies.indexOf(reply), 1);
						else
							alert(result.message);
					}, "json");
			}
		}
	});
});
