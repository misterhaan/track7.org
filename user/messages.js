const userSuggest = {
	data: {
		usermatch: "",
		findingusers: false,
		matchingusers: [],
		cursor: false
	},
	watch: {
		usermatch: function(match) {
			if(this.waitUserSuggest)
				clearTimeout(this.waitUserSuggest);
			delete this.waitUserSuggest;
			if(match.length < 3) {
				this.matchingusers = [];
				this.findingusers = false;
			} else {
				this.waitUserSuggest = setTimeout(() => {
					this.findingusers = true;
					$.get("/api/users/suggest", { match: match }, result => {
						if(!result.fail)
							this.matchingusers = result.users;
						else
							alert(result.message);
						this.findingusers = false;
					}, "json");
				}, 250);
			}
		}
	},
	methods: {
		SelectHashUser: function() {
			if(location.hash.substring(0, 5) == "#!to=")
				$.get("/api/users/info", { username: location.hash.substring(5) }, result => {
					if(!result.fail && typeof this.SelectUser == "function")
						this.SelectUser(result.user);
					else
						this.usermatch = location.hash.substring(5);
				}, "json");
		},
		HideUserSuggestions: function(delay) {
			setTimeout(() => {
				this.usermatch = "";
				this.cursor = false;
			}, +delay);
		},
		NextUser: function() {
			if(this.cursor) {
				for(var u = 0; u < this.matchingusers.length - 1; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.cursor = this.matchingusers[u + 1];
						return;
					}
			}
			this.cursor = this.matchingusers[0];
		},
		PrevUser: function() {
			if(this.cursor) {
				for(var u = 1; u < this.matchingusers.length; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.cursor = this.matchingusers[u - 1];
						return;
					}
			}
			this.cursor = this.matchingusers[this.matchingusers.length - 1];
		},
		SelectCursorUser: function() {
			if(this.cursor && typeof this.SelectUser == "function")
				for(var u = 0; u < this.matchingusers.length; u++)
					if(this.matchingusers[u] == this.cursor) {
						this.SelectUser(this.cursor);
						return;
					}
		}
	}
};

$(function() {
	if($("#conversations").length)
		var conversations = new Vue({
			el: "#messages",
			mixins: [userSuggest],
			data: {
				conversations: [],
				selected: false,
				loading: false,
				error: false
			},
			created: function() {
				this.Load();
				this.SelectUser = this.GetConversation;
				this.SelectHashUser();
			},
			methods: {
				Load: function() {
					this.loading = true;
					$.get("/api/conversations/list", {}, result => {
						if(!result.fail)
							this.conversations = result.conversations.map(c => $.extend(c, {
								messages: [],
								hasmore: false,
								loading: false,
								error: false,
								response: ""
							}));
						else
							this.error = result.message;
						this.loading = false;
					}, "json");
				},
				Select: function(conversation, replying) {
					this.selected = conversation;
					if(!conversation.messages.length && conversation.id)
						this.LoadMessages(conversation, replying);
					else if(replying)
						setTimeout(() => {
							$("form.reply:visible textarea").focus();
							$("html, body").animate({ scrollTop: $("form.reply:visible").offset().top }, 750);
							autosize($("textarea"));
						}, 50);
					else {
						var id = conversation.messages[conversation.messages.length - 1].id;
						setTimeout(() => {
							id = $("#pm" + id).is(":hidden") ? "#m" + id : "#pm" + id;
							$("html, body").animate({ scrollTop: $(id).offset().top }, 750);
							autosize($("textarea"));
						}, 50);
					}
				},
				LoadMessages: function(conversation, replying) {
					if(conversation.id) {
						conversation.loading = true;
						$.get("/api/conversations/messages", { conversation: conversation.id, before: conversation.oldest }, result => {
							if(!result.fail) {
								var id = 0;
								if(conversation.messages.length)
									id = conversation.messages[0].id;
								conversation.messages = result.messages.concat(conversation.messages);
								conversation.hasmore = result.hasmore;
								conversation.oldest = result.oldest;
								if(replying)
									setTimeout(() => {
										$("form.reply:visible textarea").focus();
										$("html, body").animate({ scrollTop: $("form.reply:visible").offset().top }, 750);
										Prism.highlightAll();
										autosize($("textarea"));
									}, 50);
								else {
									if(!id && conversation.messages.length)
										id = conversation.messages[conversation.messages.length - 1].id;
									setTimeout(() => {
										id = $("#pm" + id).is(":hidden") ? "#m" + id : "#pm" + id;
										$("html, body").animate({ scrollTop: $(id).offset().top }, 750);
										Prism.highlightAll();
										autosize($("textarea"));
									}, 50);
								}
							} else
								alert(result.message);
							conversation.loading = false;
						}, "json");
					} else if(replying)
						setTimeout(() => {
							$("form.reply:visible textarea").focus();
							$("body").animate({ scrollTop: $("form.reply:visible").offset().top }, 750);
						}, 50);
				},
				Reply: function(conversation) {
					$.post("/api/conversations/sendMessage", { to: conversation.thatuser, markdown: conversation.response }, result => {
						if(!result.fail) {
							conversation.messages.push(result.message);
							if(!conversation.oldest)
								conversation.oldest = result.timesent;
							conversation.response = "";
							setTimeout(() => {
								Prism.highlightAll();
							}, 50);
							var c = this.conversations.indexOf(conversation);
							if(c > 0) {
								this.conversations.splice(c, 1);
								this.conversations.unshift(conversation);
							}
						} else
							alert(result.message);
					}, "json");
				},
				GetConversation: function(user) {
					// open existing conversation if it exists
					for(var c in this.conversations)
						if(this.conversations[c].thatuser == user.id) {
							this.Select(this.conversations[c], true);
							this.usermatch = "";
							return;
						}
					// no existing conversation, so add a new conversation to the top of the list (as it will be the most recent)
					var conv = {
						id: false,
						thatuser: user.id,
						displayname: user.displayname || user.username,
						username: user.username,
						avatar: user.avatar,
						sent: { datetime: "", display: "0 seconds", tooltip: "" },
						issender: 1,
						hasread: 0,
						messages: [],
						hasmore: false,
						loading: false,
						error: false,
						response: ""
					};
					this.conversations.splice(0, 0, conv);
					this.Select(conv, true);
					this.usermatch = "";
				}
			}
		});
	else if($("#sendmessage").length)
		var sendmessage = new Vue({
			el: "#sendmessage",
			mixins: [userSuggest],
			data: {
				chosenuser: false,
				sentmessages: []
			},
			created: function() {
				this.SelectUser = this.Select;
				this.SelectHashUser();
			},
			methods: {
				Select: function(user) {
					this.chosenuser = user;
					this.usermatch = "";
					$("#fromname").focus();
				},
				Clear: function() {
					this.chosenuser = false;
					$("#usermatch").focus();
				},
				Send: function() {
					$.post("/api/conversations/sendMessage", { to: this.chosenuser.id, fromname: $("#fromname").val(), fromcontact: $("#fromcontact").val(), markdown: $("#markdown").val() }, result => {
						if(!result.fail) {
							this.sentmessages.push(result.message);
							this.chosenuser = false;
							$("#fromname").val("");
							$("#fromcontact").val("");
							$("#markdown").val("");
						} else
							alert(result.message);
					}, "json");
				}
			}
		});
});
