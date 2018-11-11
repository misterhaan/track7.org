$(function() {
	var main = new Vue({
		el: "main",
		data: {
			inputtype: "",
			timestamp: "",
			formatted: "",
			zone: "local",

			hasresults: false,
			resulttimestamp: "",
			smart: "",
			ago: "",
			year: "",
			month: "",
			day: "",
			weekday: "",
			time: ""
		},
		methods: {
			Analyze: function() {
				var data = {type: this.inputtype, zone: this.zone};
				if(this.inputtype == "timestamp")
					data.timestamp = this.timestamp;
				if(this.inputtype == "formatted")
					data.formatted = this.formatted;
				$.get("?ajax=analyze", data, result => {
					if(!result.fail) {
						this.resulttimestamp = result.timestamp;
						this.smart = result.smart;
						this.ago = result.ago;
						this.year = result.year;
						this.month = result.month;
						this.day = result.day;
						this.weekday = result.weekday;
						this.time = result.time;
						this.hasresults = true;
					} else
						alert(result.message);
				}, "json");
			}
		}
	});
});
