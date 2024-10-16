import "jquery";
import { createApp } from "vue";

createApp({
	name: "WebScripts",
	data() {
		return {
			scripts: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/script.php/list";
			if(this.scripts.length)
				url += "/" + this.scripts.length;

			$.get(url)
				.done(result => {
					this.scripts = this.scripts.concat(result.Scripts);
					this.hasMore = result.HasMore;
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<p v-if="!loading && !scripts.length">
			no web scripts found!  that can’t be right...
		</p>

		<article v-for="script in scripts">
			<header>
				<h2><a :href=script.ID>{{script.Title}}</a></h2>
				<p class=meta>
					<time class=posted :title="'released ' + script.Instant.Tooltip" :datetime=script.Instant.DateTime v-html=script.Instant.Display></time>
					<span class=scripttype>{{script.Type}}</span>
				</p>
			</header>
			<div v-html=script.Description></div>
		</article>
		`
}).mount("#webscripts");
