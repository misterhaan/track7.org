$(function() {
	var taginfo = new Vue({
		el: "#taginfo",
		data: {
			type: "",
			tags: [],
			descriptionedit: false,
			loading: false
		},
		computed: {
			prefix: function() {
				switch(this.type) {
					case "blog": return "showing blog entries";
					case "guide": return "showing guides dealing with";
					default: return "";
				}
			},
			postfix: function() {
				switch(this.type) {
					case "blog": return "go back to all entries.";
					case "guide": return "go back to all guides.";
					default: return "";
				}
			},
			urlPrefix: function() {
				switch(this.type) {
					case "blog": return "/bln/";
					case "guide": return "/guides/";
					case "photos": return "/album/";
					default: return "/" + this.type + "/";
				}
			},
			subsite() {
				switch(this.type) {
					case "photos": return 'album';
					default: return '';  // not migrated yet
				}
			}
		},
		methods: {
			Load: function(type) {
				this.type = type;
				this.loading = true;
				this.tags = [];
				if(this.subsite)
					$.get("/api/tag.php/stats/" + this.subsite)
						.done(result => {
							this.tags = result.map(t => { return { name: t.Name, count: t.Count, lastused: { datetime: t.LastUsed.DateTime, display: t.LastUsed.Display, title: t.LastUsed.Tooltip }, description: t.Description, editing: false }; });
						});
				else
					$.get("/api/tags/fullList", { type: type }, result => {
						if(!result.fail)
							this.tags = result.tags.map(t => $.extend(t, { editing: false }));
						else
							alert(result.message);
						this.loading = false;
					}, "json");
			},
			Edit: function(tag) {
				tag.editing = true;
				this.descriptionedit = tag.description;
				setTimeout(() => {
					autosize($("textarea"));
					$("textarea").focus();
				}, 50);
			},
			Cancel: function(tag) {
				tag.editing = false;
				this.descriptionedit = false;
			},
			Save: function(tag) {
				if(this.subsite)
					$.ajax({
						url: "/api/tag.php/description/" + this.subsite + "/" + tag.name,
						type: "PUT",
						data: this.descriptionedit
					}).done(() => {
						tag.description = this.descriptionedit;
						this.descriptionedit = false;
						tag.editing = false;
					}).fail(request => {
						alert(request.responseText);
					});
				else
					$.post("/api/tags/setdesc", { type: this.type, id: tag.id, description: this.descriptionedit }, result => {
						if(!result.fail) {
							tag.description = this.descriptionedit;
							this.descriptionedit = false;
							tag.editing = false;
						} else
							alert(result.message);
					}, "json");
			}
		}
	});
	$("nav.tabs a").click(function() {
		taginfo.Load($(this).attr("href").substring(1));
	});
	if(taginfo.type == "") {  // this needs to be after the tab click handler
		if($("a[href$='" + location.hash + "']").length)
			$("a[href$='" + location.hash + "']").click();
		else {
			$("a[href$='#blog']").click();
			if(history.replaceState)
				history.replaceState(null, null, "#blog");
			else
				location.hash = "#blog";
		}
	}
});
