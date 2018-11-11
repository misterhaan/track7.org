$(function() {
	var match = new Vue({
		el: "#match",
		data: {
			pattern: "",
			subject: "",
			all: false,
			checked: false,
			matches: []
		},
		methods: {
			Match: function() {
				$.get("?ajax=match", {pattern: this.pattern, subject: this.subject, all: +this.all}, result => {
					if(result.fail)
						alert(result.message);
					else {
						this.checked = true;
						this.matches = result.matches;
					}
				}, "json");
			}
		}
	});

	var replace = new Vue({
		el: "#replace",
		data: {
			pattern: "",
			replacement: "",
			subject: "",
			replaced: false,
			result: ""
		},
		methods: {
			Replace: function() {
				$.get("?ajax=replace", {pattern: this.pattern, replacement: this.replacement, subject: this.subject}, result => {
					if(result.fail)
						alert(result.message);
					else {
						this.replaced = true;
						this.result = result.replacedResult;
					}
				}, "json");
			}
		}
	});

	if(!$(".tabcontent:visible").length) {  // this needs to be after the tab click handlers
		if($(location.hash).length)
			$("a[href$='" + location.hash + "']").click();
		else {
			$("a[href$='#match']").click();
			if(history.replaceState)
				history.replaceState(null, null, "#match");
			else
				location.hash = "#match";
		}
	}
});
