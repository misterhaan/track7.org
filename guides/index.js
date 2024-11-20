import "jquery";
import { createApp } from "vue";
import "tag";

createApp({
	name: "Guides",
	data() {
		return {
			guides: [],
			drafts: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.tagid = document.location.pathname.split("/")[2];
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/guide.php/list";
			if(this.tagid)
				url += "/" + this.tagid;
			if(this.guides.length)
				url += "/" + this.guides.length;

			$.get(url)
				.done(result => {
					this.guides = this.guides.concat(result.Guides);
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

		<h2 v-if=drafts.length>drafts</h2>
		<ul v-if=drafts.length>
			<li v-for="draft in drafts"><a :href=draft.URL>{{draft.Title}}</a></li>
		</ul>

		<p v-if="!guides.length && !loading">
			no guides!  how will we know what to do?
		</p>

		<article v-for="guide in guides">
			<header class=floatbgstop>
				<h2><a :href="(tagid ? '../' : '') + guide.ID" title="read this guide">{{guide.Title}}</a></h2>
				<p class=guidemeta>
					<span class=guidelevel :title="guide.Level + ' level'">{{guide.Level}}</span>
					<span class=tags v-if=guide.Tags.length :title="guide.Tags.length == 1 ? '1 tag' : guide.Tags.length + ' tags'">
						<template v-for="(tag, index) in guide.Tags">{{index ? ', ' : ''}}<a class=tag :href="(tagid ? '../' : '') + tag + '/'" :title="'guides tagged ' + tag">{{tag}}</a></template>
					</span>
					<span class=views :title="'viewed ' + guide.Views + ' times'">{{guide.Views}}</span>
					<span class=rating :data-stars=Math.round(guide.Rating*2)/2 :title="'rated ' + guide.Rating + ' stars by ' + (guide.Votes == 0 ? 'nobody' : (guide.Votes == 1 ? '1 person' : guide.Votes + ' people'))"></span>
					<time class=posted v-html=guide.Instant.Display :datetime=guide.Instant.DateTime :title="'posted ' + (guide.Posted ? guide.Instant.Tooltip + ' (originally ' + guide.Posted + ')' : guide.Instant.Tooltip)"></time>
					<span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
				</p>
			</header>
			<div class=summary v-html=guide.Summary>
			</div>
			<footer>
				<p class=readmore>
					<a class=continue :href=guide.ID>read this guide</a>
				</p>
			</footer>
		</article>

		<p class=loading v-if=loading>loading more guides . . .</p>
		<p class="more calltoaction" v-if="hasMore && !loading"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more guides</a></p>
		`
}).mount("#guides");
