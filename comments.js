import "jquery";
import { Comment } from "comment";
import { createApp } from "vue";

createApp({
	name: "RecentComments",
	data() {
		return {
			comments: [],
			hasMore: false,
			loading: false,
			error: ""
		};
	},
	created() {
		this.user = $("#recentcomments").data("user");
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			let url = this.user ? "/api/comment.php/byuser/" + this.user : "/api/comment.php/all";
			if(this.comments.length)
				url += "/" + this.comments.length;
			$.get(url)
				.done(result => {
					this.comments = this.comments.concat(result.Comments);
					this.hasMore = result.HasMore;
					this.$nextTick(() => {
						Prism.highlightAll();
					});
				}).fail(request => {
					this.error = request.responseText;
				}).always(() => {
					this.loading = false;
				});
		},
		Delete(index) {
			$.ajax({ url: "/api/comment.php/id/" + this.comments[index].ID, type: "DELETE" })
				.done(() => {
					this.comments.splice(index, 1);
				}).fail(request => {
					alert(request.responseText);
				});
		}
	},
	template: /* html */ `
		<p class=info v-if="!loading && !error && !comments.length">no comments found</p>

		<template v-for="(comment, index) in comments" :key=comment.ID>
			<h3><a :href=comment.URL>{{comment.Title}}</a></h3>
			<Comment :comment=comment @delete=Delete(index)></Comment>
		</template>

		<p class=error v-if="error">{{error}}</p>
		<p class=loading v-if=loading>loading comments . . .</p>
		<p class=calltoaction v-if=hasMore><a class="get action" href="/api/comment.php/recent" v-on:click.prevent=Load>load more comments</a></p>
		`
}).component("Comment", Comment)
	.mount("#recentcomments");
