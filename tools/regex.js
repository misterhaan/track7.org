import "jquery";
import { createApp } from "vue";
import autosize from "autosize";

createApp({
	data() {
		return {
			tab: "match",
			pattern: "",
			findAll: false,
			replacement: "",
			subject: "",
			matches: [],
			result: ""
		};
	},
	created() {
		if(location.hash) {
			const hash = location.hash.slice(1);
			switch(hash) {
				case "match":
					this.Autosize();
					return;
				case "replace":
					this.tab = hash;
					return;
			}
		}

		if(history.replaceState)
			history.replaceState(null, null, "#" + this.tab);
		else
			location.hash = "#" + this.tab;
		this.Autosize();
	},
	watch: {
		tab() {
			this.Autosize();
		}
	},
	methods: {
		Autosize() {
			this.$nextTick(() => {
				autosize(this.$refs.editField);
			});
		},
		Match() {
			const url = this.all ? "/api/tool.php/regexmatch/all" : "/api/tool.php/regexmatch";
			$.post(url, { pattern: this.pattern, subject: this.subject }).done(result => {
				this.matches = result;
			}).fail(request => {
				alert(request.responseText);
			});
		},
		Replace() {
			$.post("/api/tool.php/regexreplace", { pattern: this.pattern, replacement: this.replacement, subject: this.subject }).done(result => {
				this.result = result;
			}).fail(request => {
				alert(request.responseText);
			});
		}
	},
	template: /* html */ `
			<nav class=tabs>
				<a href=#match :class="{selected: this.tab == 'match'}" title="preg_match and preg_match_all" @click="this.tab = 'match'">match</a>
				<a href=#replace :class="{selected: this.tab == 'replace'}" title="preg_replace" @click="this.tab = 'replace'">replace</a>
			</nav>

			<section id=match class=tabcontent v-if="tab == 'match'">
				<h2>match</h2>
				<p class=meta>
					using php function
					<a href="https://php.net/preg_match" v-if=!all>preg_match</a>
					<a href="https://php.net/preg_match_all" v-if=all>preg_match_all</a>
				</p>
				<form class=regextest @submit.prevent=Match>
					<label>
						<span class=label>pattern:</span>
						<span class=field><input v-model=pattern></span>
					</label>
					<label class=multiline>
						<span class=label>subject:</span>
						<span class=field><textarea v-model=subject ref=editField></textarea></span>
					</label>
					<label class=checkbox>
						<span class=label></span>
						<span class="checkbox"><input type=checkbox v-model=all>find all matches</span>
					</label>
					<button>match</button>
				</form>
				<p v-if="matches.length < 1">no matches found</p>
				<ol class=matches>
					<li v-for="match in matches">
						<pre><code>{{match}}</code></pre>
					</li>
				</ol>
			</section>

			<section id=replace class=tabcontent v-if="tab == 'replace'">
				<h2>replace</h2>
				<p class=meta>
					using php function <a href="http://php.net/preg_replace">preg_replace</a>
				</p>
				<form class=regextest @submit.prevent=Replace>
					<label>
						<span class=label>pattern:</span>
						<span class=field><input v-model=pattern></span>
					</label>
					<label>
						<span class=label>replace:</span>
						<span class=field><input v-model=replacement></span>
					</label>
					<label class=multiline>
						<span class=label>subject:</span>
						<span class=field><textarea v-model=subject ref=editField></textarea></span>
					</label>
					<button>replace</button>
				</form>
				<pre v-if=result><code>{{result}}</code></pre>
			</section>
	`
}).mount("#regextest");
