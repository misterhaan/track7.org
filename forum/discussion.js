$(function() {
	if(window.location.hash.substring(0, 2) == "#p")
		$.get("/api/forum/replyid", {postid: window.location.hash.substring(2)}, result => {
			if(!result.fail)
				window.location.hash = "#r" + result.id;
			else
				alert(result.message);
		}, "json");

	var discussion = new Vue({
		el: "#discussion",
		data: {
			replies: [],
			saving: false
		},
		created: function() {
			this.discussion = $("h1[data-discussion]").data("discussion");
			$.get("/api/forum/discussion", {discussion: this.discussion}, result => {
				if(!result.fail) {
					this.replies = this.replies.concat(result.replies.map(r => {r.editing = false; return r;}));
					setTimeout(() => {
						Prism.highlightAll();
						autosize($("textarea[name='markdown']"));
						if(window.location.hash && $(window.location.hash).length)
							$("html, body").animate({scrollTop: $(window.location.hash).offset().top}, 750);
					}, 25);
				} else
					alert(result.message);
			});
		},
		methods: {
			AddReply: function() {
				this.saving = true;
				var fdata = $("#addreply").serializeArray();
				fdata.push({name:"discussion", value: this.discussion});
				$.post("/api/forum/reply", fdata, result => {
					if(!result.fail) {
						result.reply.editing = false;
						this.replies = this.replies.concat(result.reply);
						$("#addreply textarea").val("");
					} else
						alert(result.message);
					this.saving = false;
				}, "json");
			},
			EditReply: function(reply) {
				reply.savemarkdown = reply.markdown;
				reply.editing = true;
				setTimeout(() => {
					var ta = $("#r" + reply.id + " textarea");
					autosize(ta);
					$("html, body").animate({scrollTop: ta.offset().top}, 750);
					ta.focus();
				}, 25);
			},
			UneditReply: function(reply) {
				reply.editing = false;
				reply.markdown = reply.savemarkdown;
				delete reply.savemarkdown;
			},
			SaveReply: function(reply, stealth) {
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
			DeleteReply: function(reply) {
				if(confirm("deleted replies cannot be recovered.  are you sure you want to delete?"))
					$.post("/api/forum/delete", {id: reply.id}, result => {
						if(!result.fail)
							if(result.discussionDeleted)
								window.location.href = ".";
							else
								this.replies.splice(this.replies.indexOf(reply), 1);
						else
							alert(result.message);
					}, "json");
			}
		}
	});
});
