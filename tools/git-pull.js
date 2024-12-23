import "jquery";
import { createApp } from "vue";

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
		Update() {
			this.working = true;
			$.post("/api/tool.php/gitpull").done(result => {
				this.results = [result, ...this.results];
			}).fail(request => {
				this.error = request.responseText;
			}).always(() => {
				this.working = false;
			});
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
