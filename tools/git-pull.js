import { createApp } from "vue";
import ToolApi from "/api/tool.js";

createApp({
	name: "GitPull",
	data() {
		return {
			results: [],
			error: "",
			working: false
		};
	},
	methods: {
		async Update() {
			this.working = true;
			try {
				const result = await ToolApi.gitpull();
				this.results = [result, ...this.results];
			} catch(error) {
				this.error = error.message;
			} finally {
				this.working = false;
			}
		}
	},
	template: /* html */ `
		<nav class=actions><a class=get :class="{working: working}" href="#pull" @click.prevent=Update>update</a></nav>

		<p class=error v-if=error>{{error}}</p>

		<article v-for="result in results">
			<h2>git pull returned {{result.ReturnCode}} at <time :datetime=result.Instant.DateTime :title=result.Instant.Tooltip v-html=result.Instant.Display></time></h2>
			<pre><code>{{result.Output}}</code></pre>
			<section v-if="result.CacheDelete && result.CacheDelete.length">
				<p>deleted {{result.CacheDelete.length}} files from cloudflare cache:</p>
				<ul><li v-for="file in result.CacheDelete">{{file}}</li></ul>
				<h3>cloudflare returned {{result.Cloudflare.Code}}</h3>
				<pre class=language-json><code class=language-json>{{result.Cloudflare.Text}}</code></pre>
			</section>
		</article>
	`
}).mount("#gitpull");
