import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { currentUser } from "user";
import CommentApi from "/api/comment.js";

const subsite = location.pathname.split("/")[1];

export const Comment = {
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
		trusted() {
			return currentUser?.Level >= "3-trusted";
		},
		canSave() {
			return !this.saving && this.comment.Markdown && this.oldMarkdown != this.comment.Markdown.trim();
		}
	},
	methods: {
		async Edit() {
			this.editing = true;
			this.oldMarkdown = this.comment.Markdown;
			await nextTick();
			this.$refs.editField.focus();
			autosize(this.$refs.editField);
		},
		async Unedit() {
			this.editing = false;
			this.comment.Markdown = this.oldMarkdown;
			this.oldMarkdown = '';
			await nextTick();
			Prism.highlightAll();
		},
		async Save(leaveANote) {
			this.saving = true;
			try {
				const html = await CommentApi.patch(this.comment.ID, this.comment.Markdown, !leaveANote);
				this.editing = false;
				this.comment.HTML = html;
				if(leaveANote)
					this.comment.Edits.push({ Instant: { Display: "just now" }, DisplayName: currentUser.DisplayName, Username: currentUser.URL.split("/")[1] });
				await nextTick();
				Prism.highlightAll();
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
		},
		Delete() {
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
				<div class=meta>
					<div class=edithistory v-for="edit in comment.Edits">
						edited
						<time :datetime=edit.Instant.DateTime v-html=edit.Instant.Display></time>
						by
						<a :href="'/user/' + edit.Username + '/'">{{edit.DisplayName}}</a>
					</div>
				</div>
				<footer v-if=comment.CanChange>
					<button class="okay action link" :class="{working: saving}" v-if=editing :disabled=!canSave @click=Save(true) title="save changes and leave an edit note">save</button>
					<button class="okay action link" :class="{working: saving}" v-if="editing && trusted" :disabled=!canSave @click=Save(false) title="save changes without leaving an edit note">stealth save</button>
					<a class="cancel action" v-if=editing @click.prevent=Unedit href="#cancelEdit">cancel</a>
					<a class="edit action" v-if=!editing @click.prevent=Edit href="#edit">edit</a>
					<a class="del action" v-if=!editing @click.prevent="Delete" href="/api/comment.php">delete</a>
				</footer>
			</div>
		</section>
	`
};

const comments = document.querySelector("#comments");
if(comments) {
	const postID = comments.dataset.post;

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
			user() {
				return currentUser;
			},
			canSave() {
				return !this.saving && this.newComment.markdown.trim() && (this.user || this.newComment.name.trim());
			},
			heading() {
				return subsite != "forum";
			},
			commentLabel() {
				return subsite == "forum" ? "reply" : "comment";
			},
			commentLabelPlural() {
				return subsite == "forum" ? "replies" : "comments";
			}
		},
		async created() {
			this.Load();
			await nextTick();
			autosize(this.$refs.commentField);
		},
		methods: {
			async Add() {
				this.saving = true;
				try {
					const comment = await CommentApi.new(postID, this.newComment);
					this.comments.push(comment);
					this.newComment.markdown = "";
				} catch(error) {
					alert(error.message);
				} finally {
					this.saving = false;
				}
			},
			async Load() {
				this.loading = true;
				try {
					const result = await CommentApi.byPost(postID, this.comments.length);
					this.comments = this.comments.concat(result.Comments);
					this.hasMore = result.HasMore;
					await nextTick();
					Prism.highlightAll();
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
		template: /*html*/ `
			<h2 v-if=heading>comments</h2>
			<p class=error v-if=error>{{error}}</p>
			<p v-if="!loading && !comments.length">
				there are no {{commentLabelPlural}} so far. you could be the first!
			</p>

			<Comment v-for="(comment, index) in comments" :key=comment.ID :comment=comment @delete=Delete(index)></Comment>

		<p class=loading v-if=loading>loading more {{commentLabelPlural}} . . .</p>
		<p class="more calltoaction" v-if=hasMore><a class="action get" href=#nextpage @click.prevent=Load>load more {{commentLabelPlural}}</a></p>

			<form id=addcomment @submit.prevent=Add>
				<label v-if=user :title="'you are signed in, so your ' + commentLabel + ' will post with your avatar and a link to your profile'">
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
					<span class=label>{{commentLabel}}:</span>
					<span class=field><textarea ref=commentField v-model=newComment.markdown></textarea></span>
				</label>
				<button :class="{working: saving}" :disabled=!canSave>post {{commentLabel}}</button>
			</form>

		`
	}).component("Comment", Comment)
		.mount("#comments");
}
