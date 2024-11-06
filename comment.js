import "jquery";
import { GetCurrentUser } from "user";
import { createApp } from "vue";
import autosize from "autosize";

const user = GetCurrentUser();

const comments = document.querySelector("#comments");
if(comments) {
	const postID = $(comments).data("post");

	const Comment = {
		props: ["comment"],
		emits: ["delete"],
		data() {
			return {
				editing: false,
				oldMarkdown: '',
				saving: false
			};
		},
		computed: {
			canSave: function() {
				return !this.saving && this.comment.Markdown && this.oldMarkdown != this.comment.Markdown.trim();
			}
		},
		methods: {
			Edit: function() {
				this.editing = true;
				this.oldMarkdown = this.comment.Markdown;
				this.$nextTick(() => {
					this.$refs.editField.focus();
					autosize(this.$refs.editField);
				});
			},
			Unedit: function() {
				this.editing = false;
				this.comment.Markdown = this.oldMarkdown;
				this.oldMarkdown = '';
				this.$nextTick(() => {
					Prism.highlightAll();
				});
			},
			Save: function() {
				this.saving = true;
				$.ajax({ url: "/api/comment.php/id/" + this.comment.ID, type: "PATCH", data: this.comment.Markdown })
					.done(html => {
						this.editing = false;
						this.comment.HTML = html;
						this.$nextTick(() => {
							Prism.highlightAll();
						});
					}).fail(request => {
						alert(request.responseText);
					}).always(() => {
						this.saving = false;
					});
			},
			Delete: function() {
				if(confirm("do you really want to delete your comment?  there’s no coming back!")) {
					this.$emit("delete", this.comment);
				}
			}
		},
		template: /* html */ `
			<section class=comment>
				<div class=userinfo>
					<div class=username :class="{friend: comment.Author.IsFriend}" :title="comment.Author.IsFriend ? comment.Author.Name + ' is your friend' : null">
						<a v-if=comment.Author.URL :href=comment.Author.URL>{{comment.Author.Name}}</a>
						<template v-if=!comment.Author.URL>{{comment.Author.Name}}</template>
					</div>
					<a v-if=comment.Author.Avatar :href=comment.Author.URL><img class=avatar alt="" :src=comment.Author.Avatar></a>
					<div v-if=comment.Author.Level class=userlevel>{{comment.Author.Level}}</div>
				</div>
				<div class=comment>
					<header>posted <time :datetime=comment.Instant.DateTime v-html=comment.Instant.Display></time></header>
					<div v-if=!editing class=content v-html=comment.HTML></div>
					<div v-if=editing class="content edit">
						<textarea v-model=comment.Markdown ref=editField></textarea>
					</div>
					<footer v-if=comment.CanChange>
						<button class="okay action link" :class="{working: saving}" v-if=editing :disabled=!canSave @click=Save>save</button>
						<a class="cancel action" v-if=editing @click.prevent=Unedit href="#cancelEdit">cancel</a>
						<a class="edit action" v-if=!editing @click.prevent=Edit href="#edit">edit</a>
						<a class="del action" v-if=!editing @click.prevent="Delete" href="/api/comment.php">delete</a>
					</footer>
				</div>
			</section>
		`
	};

	createApp({
		name: "Comments",
		data() {
			return {
				comments: [],
				hasMore: false,
				newComment: {
					name: "",
					contact: "",
					markdown: ""
				},
				loading: false,
				saving: false,
				error: ""
			};
		},
		computed: {
			user: function() {
				return user;
			},
			canSave: function() {
				return !this.saving && this.newComment.markdown.trim() && (this.user || this.newComment.name.trim());
			}
		},
		created: function() {
			this.Load();
			this.$nextTick(() => {
				autosize(this.$refs.commentField);
			});
		},
		methods: {
			Add: function() {
				this.saving = true;
				$.ajax({ url: "/api/comment.php/new/" + postID, type: "POST", data: this.newComment })
					.done(comment => {
						this.comments.push(comment);
						this.newComment.markdown = "";
					}).fail(request => {
						alert(request.responseText);
					}).always(() => {
						this.saving = false;
					});
			},
			Load: function() {
				this.loading = true;
				let url = "/api/comment.php/bypost/" + postID;
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
			Delete: function(index) {
				$.ajax({ url: "/api/comment.php/id/" + this.comments[index].ID, type: "DELETE" })
					.done(() => {
						this.comments.splice(index, 1);
					}).fail(request => {
						alert(request.responseText);
					});
			}
		},
		template: /*html*/ `
			<h2>comments</h2>
			<p class=error v-if=error>{{error}}</p>
			<p v-if="!loading && !comments.length">
				there are no comments so far. you could be the first!
			</p>

			<Comment v-for="(comment, index) in comments" :key=comment.id :comment=comment @delete=Delete(index)></Comment>

		<p class=loading v-if=loading>loading more comments . . .</p>
		<p class="more calltoaction" v-if=hasMore><a class="action get" href=#nextpage @click.prevent=Load>load more comments</a></p>

			<form id=addcomment @submit.prevent=Add>
				<label v-if=user title="you are signed in, so your comment will post with your avatar and a link to your profile">
					<span class=label>name:</span>
					<span class=field><a :href=user.URL><img class="inline avatar" :src=user.Avatar> {{user.DisplayName}}</a></span>
				</label>
				<template v-if=!user>
					<label title="please sign in or enter a name so we know what to call you">
						<span class=label>name:</span>
						<span class=field><input v-model=newComment.name></span>
					</label>
					<label title="enter a website, web page, or e-mail address if you want people to be able to find you">
						<span class=label>contact:</span>
						<span class=field><input v-model=newComment.contact></span>
					</label>
				</template>
				<label class=multiline title="enter your comments using markdown">
					<span class=label>comment:</span>
					<span class=field><textarea ref=commentField v-model=newComment.markdown></textarea></span>
				</label>
				<button :class="{working: saving}" :disabled=!canSave>post comment</button>
			</form>

		`
	}).component("Comment", Comment)
		.mount("#comments");
}
