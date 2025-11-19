import "jquery";
import { createApp } from "vue";
import "tag";

createApp({
	name: "BlogEntries",
	data() {
		return {
			entries: [],
			drafts: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.tagid = location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/blog.php/list";
			if(this.tagid)
				url += "/" + this.tagid;
			if(this.entries.length)
				url += "/" + this.entries.length;

			$.get(url)
				.done(result => {
					this.entries = this.entries.concat(result.Entries);
					this.drafts = result.Drafts || [];
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

		<h2 v-if=drafts.length>draft entries</h2>
		<ul v-if=drafts.length>
			<li v-for="draft in drafts"><a :href=draft.URL>{{draft.Title}}</a></li>
		</ul>

		<p v-if="!loading && !entries.length">
			this blog is empty!
		</p>

		<article v-for="entry in entries">
			<header class=floatbgstop>
				<h2><a :href=entry.ID title="view this post with its comments">{{entry.Title}}</a></h2>
				<p class=meta>
					<span class=tags v-if=entry.Tags.length :title="entry.Tags.length == 1 ? '1 tag' : entry.Tags.length + ' tags'">
						<template v-for="(tag, index) in entry.Tags">{{index ? ', ' : ''}}<a :href="(tagid ? '../' : '') + tag + '/'" :title="'entries tagged ' + tag">{{tag}}</a></template>
					</span>
					<time v-if=entry.Instant class=posted v-html=entry.Instant.Display :datetime=entry.Instant.DateTime :title="'posted ' + entry.Instant.Tooltip"></time>
					<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
					<a class=comments :href="entry.ID + '#comments'" :title="(entry.CommentCount ? 'join' : 'start') + ' the discussion on this entry'">{{entry.CommentCount}}</a>
				</p>
			</header>
			<div class=entrycontent v-html=entry.Preview>
			</div>
			<footer>
				<p class="actions readmore">
					<a class=continue :href=entry.ID title="read the rest of this entry">continue reading</a>
				</p>
			</footer>
		</article>

		<p class=loading v-if=loading>loading more entries . . .</p>
		<p class="more calltoaction" v-if=hasMore><a class="action get" href=#nextpage @click.prevent=Load>load more entries</a></p>
	`
}).mount("#blogentries");
