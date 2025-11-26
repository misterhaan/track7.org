import { createApp } from "vue";
import { Comment } from "comment";
import CommentApi from "/api/comment.js";

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
		this.user = document.querySelector("#recentcomments").dataset.user;
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await (this.user
					? CommentApi.byUser(this.user, this.comments.length)
					: CommentApi.all(this.comments.length));
				this.comments = this.comments.concat(result.Comments);
				this.hasMore = result.HasMore;
				this.$nextTick(() => {
					Prism.highlightAll();
				});
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		},
		async Delete(index) {
			try {
				await CommentApi.delete(this.comments[index].ID);
				this.comments.splice(index, 1);
			} catch(error) {
				alert(error.message);
			}
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
		<p class=calltoaction v-if=hasMore><a class="get action" href="/api/comment.php/recent" @click.prevent=Load>load more comments</a></p>
		`
}).component("Comment", Comment)
	.mount("#recentcomments");
