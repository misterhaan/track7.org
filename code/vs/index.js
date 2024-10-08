import "jquery";
import { createApp } from "vue";

createApp({
	data() {
		return {
			applications: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.is64 = IsUserAgent64Bit();
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/application.php/list";
			if(this.applications.length)
				url += "/" + this.applications.length;

			$.get(url)
				.done(result => {
					this.applications = this.applications.concat(result.Applications);
					this.hasMore = result.HasMore;
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
		},
		DownloadURL(app) {
			if(!this.is64 && app.Bin32URL)
				return app.Bin32URL;
			return app.BinURL;
		},
		DownloadTitle(app) {
			if(!app.Bin32URL)
				return "download latest release";
			if(this.is64)
				return "download latest release (64-bit)";
			return "download latest release (32-bit)";
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<p v-if="!loading && !applications.length">
			no applications released!  that can’t be right...
		</p>

		<article v-for="app in applications">
			<header>
				<h2><a :href=app.ID><img class=icon :src="'files/' + app.ID + '.png'" alt=""> {{app.Title}}</a></h2>
				<p class=meta>
					<span v-if=app.Version class=version :title="'latest version ' + app.Version">v{{app.Version}}</span>
					<time class=posted :title="'latest release ' + app.Instant.Tooltip" :datetime=app.Instant.DateTime v-html=app.Instant.Display></time>
				</p>
			</header>
			<div v-html=app.Description></div>
			<p class=downloads v-if=DownloadURL(app)><a class="action download" :href=DownloadURL(app)>{{DownloadTitle(app)}}</a></p>
			<p class=downloads><a class="action list" :href=app.ID>other versions and source code</a></p>
		</article>
	`
}).mount("#vsapps");

function IsUserAgent64Bit() {
	const ua = navigator.userAgent.toLowerCase();
	return ua.includes("x86_64") || ua.includes("x86-64") || ua.includes("win64") || ua.includes("x64;") || ua.includes("amd64") || ua.includes("wow64") || ua.includes("x64_64");
}
