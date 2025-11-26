import { createApp } from "vue";
import "comment";
import ReleaseApi from "/api/release.js";

createApp({
	name: "Releases",
	data() {
		return {
			releases: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created() {
		this.application = location.pathname.split("/")[3];
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await ReleaseApi.list(this.application, this.releases.length);
				this.releases = this.releases.concat(result.Releases);
				this.hasMore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		},
		GetExtension(url) {
			const parts = url.split(".");
			return parts[parts.length - 1];
		},
		GetDownloadName(release, bitness) {
			const url = bitness == 32 ? release.Bin32URL : release.BinURL;
			let name;
			switch(this.GetExtension(url)) {
				case "msi":
					name = "installer";
					break;
				case "zip":
					name = "binaries";
					break;
			}
			name += " v" + release.Version;
			if(bitness == 32 || release.Bin32URL)
				name += " (" + bitness + "-bit)";
			return name;
		},
		GetCodeType(url) {
			if(url.startsWith("https://github.com/"))
				return "github";
			if(url.startsWith("https://") || url.startsWith("http://"))
				return "branch";
			return "zip";
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<p v-if="!loading && !releases.length">
			this application hasn’t had a release yet.
		</p>

		<article v-for="release in releases">
			<header>
				<h2>version {{release.Version}}</h2>
				<p class=meta>
					<time class=posted :datetime=release.Instant.DateTime :title="'released ' + release.Instant.Tooltip" v-html=release.Instant.Display></time>
					<span class=lang>{{release.Language}}</span>
					<span v-if=release.DotNet class=dotnet>.net {{release.DotNet.toFixed(1)}}</span>
					<span class=studio>visual studio {{release.VisualStudio}}</span>
				</p>
				<div v-if=release.Changelog v-html=release.Changelog></div>
			</header>
			<p class=downloads>
				<a :class="'action ' + GetExtension(release.BinURL)" :href=release.BinURL>{{GetDownloadName(release, 64)}}</a>
				<a v-if=release.Bin32URL :class="'action ' + GetExtension(release.Bin32URL)" :href=release.Bin32URL>{{GetDownloadName(release, 32)}}</a>
				<a v-if=release.SourceURL :class="'action ' + GetCodeType(release.SourceURL)" :href=release.SourceURL>source v{{release.Version}}</a>
			</nav>
		</article>
	`
}).mount("#releases");
