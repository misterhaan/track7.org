import "jquery";
import { createApp } from "vue";
import "tag";

createApp({
	name: "DiscussionList",
	data() {
		return {
			discussions: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created: function() {
		this.tagid = document.location.pathname.split("/")[2].replaceAll("+", " ");
		this.Load();
	},
	methods: {
		Load: function() {
			this.loading = true;

			let url = "/api/forum.php/list";
			if(this.tagid)
				url += "/" + this.tagid;
			if(this.discussions.length)
				url += "/" + this.discussions.length;

			$.get(url)
				.done(result => {
					this.discussions = this.discussions.concat(result.Discussions);
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
		<p v-if="!loading && !discussions.length">this forum is empty!</p>

		<div class=discussion v-for="disc in discussions">
			<h2><a :href="'/forum/' + disc.ID">{{disc.Title}}</a></h2>
			<p class=meta>
				<span class=tags><template v-for="(tag, index) in disc.Tags">{{index ? ', ' : ''}}<a :href="'/forum/' + tag.replaceAll(' ', '+') + '/'">{{tag}}</a></template></span>
				<span class=firstpost :title="'started ' + disc.StartInstant.Tooltip + ' by ' + disc.StarterName">
					<time :datetime=disc.StartInstant.DateTime>{{disc.StartInstant.Display}} ago</time>
					by
					<a v-if="disc.StarterContact" :href=disc.StarterContact>{{disc.StarterName}}</a>
					<span v-if="!disc.StarterContact">{{disc.StarterName}}</span>
				</span>
				<span class=replies :title="disc.ReplyCount == 1 ? '1 reply' : disc.ReplyCount + ' replies'">{{disc.ReplyCount}}</span>
				<span v-if=disc.ReplyCount class=lastpost :title="'last reply ' + disc.LatestInstant.Tooltip + ' by ' + disc.LatestName">
					<time datetime=disc.LatestInstant.DateTime>{{disc.LatestInstant.Display}} ago</time>
					by
					<a v-if="disc.LatestContact" :href=disc.LatestContact>{{disc.LatestName}}</a>
					<span v-if="!disc.LatestContact">{{disc.LatestName}}</span>
				</span>
			</p>
		</div>

		<p class=loading v-if=loading>loading more discussions . . .</p>
		<p class="more calltoaction" v-if=hasMore><a class="action get" href=#nextpage @click.prevent=Load>load more discussions</a></p>
		`
}).mount("#discussionlist");
