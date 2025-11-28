import { createApp, nextTick } from "vue";
import autosize from "autosize";
import { currentUser } from "user";
import { ExistingTagsField } from "tag";
import ForumApi from "/api/forum.js";

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
		canSave() {
			return !this.saving && this.title.trim() && this.tags.trim() && this.message.trim();
		},
		user() {
			return currentUser;
		}
	},
	async created() {
		await nextTick();
		autosize(this.$refs.textField);
	},
	methods: {
		TagsChanged(newTags) {
			this.tags = newTags;
		},
		async Save() {
			this.saving = true;
			try {
				const result = await ForumApi.start(
					this.title,
					this.tags,
					this.message,
					currentUser,
					this.name,
					this.contact
				);
				location.href = result;
			} catch(error) {
				alert(error.message);
			} finally {
				this.saving = false;
			}
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
