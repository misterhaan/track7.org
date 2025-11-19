import "jquery";
import { currentUser } from "user";
import { createApp } from "vue";
import { ExistingTagsField } from "tag";
import autosize from "autosize";

createApp({
	name: "StartDiscussion",
	data() {
		return {
			name: "",
			contact: "",
			title: "",
			tags: "",
			message: "",
			saving: false
		};
	},
	computed: {
		canSave: function() {
			return !this.saving && this.title.trim() && this.tags.trim() && this.message.trim();
		},
		user: function() {
			return currentUser;
		}
	},
	created() {
		this.$nextTick(() => {
			autosize(this.$refs.textField);
		});
	},
	methods: {
		TagsChanged(newTags) {
			this.tags = newTags;
		},
		Save() {
			this.saving = true;
			const data = {
				title: this.title,
				tags: this.tags,
				message: this.message
			};
			if(!currentUser) {
				data.name = this.name;
				data.contact = this.contact;
			}
			$.post("/api/forum.php/start", data).done(result => {
				location.href = result;
			}).fail(request => {
				alert(request.responseText);
			}).always(() => {
				this.saving = false;
			});
		}
	},
	template: /* html */ `
		<form @submit.prevent=Save>
			<label v-if=user>
				<span class=label>name:</span>
				<span class=field><a :href=user.URL><img class="inline avatar" :src=user.Avatar> {{user.DisplayName}}</a></span>
			</label>
			<label v-if=!user title="tell us your name to make it easier to talk to you, or better yet:  sign in!">
				<span class=label>name:</span>
				<span class=field><input v-model=name placeholder="random internet person" maxlength=48></span>
			</label>
			<label v-if=!user title="leave a contact url or e-mail address to give people another option of contacting you">
				<span class=label>contact:</span>
				<span class=field><input v-model=contact maxlength=255></span>
			</label>
			<label>
				<span class=label>title:</span>
				<span class=field><input v-model=title maxlength=128 required></span>
			</label>
			<ExistingTagsField @changed=TagsChanged />
			<label class=multiline title="your message to start the discussion (you can use markdown here)">
				<span class=label>message:</span>
				<span class=field><textarea rows="" cols="" v-model=message ref=textField></textarea></span>
			</label>
			<button :disabled=!canSave :class="{working: saving}">start discussion</button>
		</form>
	`
}).component("ExistingTagsField", ExistingTagsField)
	.mount("#editdiscussion");;
