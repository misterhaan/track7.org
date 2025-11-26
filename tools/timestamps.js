import { createApp } from "vue";
import ToolApi from "/api/tool.js";

createApp({
	name: "Timestamps",
	data() {
		return {
			inputtype: "timestamp",
			timestamp: "",
			formatted: "",
			zone: "local",

			resulttimestamp: "",
			smart: "",
			ago: "",
			year: "",
			month: "",
			day: "",
			weekday: "",
			time: ""
		};
	},
	methods: {
		async Analyze() {
			try {
				const result = await ToolApi.timestamp(this.inputtype == "formatted" ? this.formatted : +this.timestamp, this.zone, this.inputtype == "formatted");
				this.resulttimestamp = result.timestamp;
				this.smart = result.smart;
				this.ago = result.ago;
				this.year = result.year;
				this.month = result.month;
				this.day = result.day;
				this.weekday = result.weekday;
				this.time = result.time;
			} catch(error) {
				alert(error.message);
			}
		}
	},
	template: /* html */ `
		<form @submit.prevent=Analyze>
			<fieldset class=selectafield>
				<div>
					<label class=label><input type=radio name=inputtype value=timestamp v-model=inputtype>timestamp:</label>
					<label class=field><input type=number v-model=timestamp maxlength=10 step=1 min=0 max=4294967295></label>
				</div>
				<div>
					<label class=label><input type=radio name=inputtype value=formatted v-model=inputtype>formatted:</label>
					<label class=field><input v-model=formatted maxlength=64></label>
				</div>
			</fieldset>
			<fieldset class=checkboxes>
				<legend>time zone:</legend>
				<span class=field>
					<label class=checkbox>
						<input type=radio name=zone value=local v-model=zone>
						local
					</label>
					<label class=checkbox>
						<input type=radio name=zone value=utc v-model=zone>
						utc
					</label>
				</span>
			</fieldset>
			<button>analyze</button>
		</form>

		<section v-if=resulttimestamp>
			<h2>results</h2>
			<dl id=timestampdata>
				<dt>timestamp</dt>
				<dd>{{resulttimestamp}}</dd>
				<dt>smart</dt>
				<dd v-html=smart></dd>
				<dt>ago</dt>
				<dd>{{ago}}</dd>
				<dt>year</dt>
				<dd>{{year}}</dd>
				<dt>month</dt>
				<dd>{{month}}</dd>
				<dt>day</dt>
				<dd>{{day}}</dd>
				<dt>weekday</dt>
				<dd>{{weekday}}</dd>
				<dt>time</dt>
				<dd>{{time}}</dd>
			</dl>
		</section>
	`
}).mount("#timestamps");
