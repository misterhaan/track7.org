import "jquery";
import { currentUser } from "user";
import { createApp } from "vue";
import autosize from "autosize";

createApp({
	name: "TagInfo",
	data() {
		return {
			subsites: [
				{ dir: "art", name: "art" },
				{ dir: "bln", name: "blog entries" },
				{ dir: "forum", name: "discussions" },
				{ dir: "guides", name: "guides" },
				{ dir: "album", name: "photos" }
			],
			subsite: "",
			tags: [],
			edit: false,
			loading: false
		};
	},
	created() {
		const hashSubsite = location.hash.substring(1);
		if(this.subsites.every(s => s.dir != hashSubsite)) {
			const firstSubsite = this.subsites[0].dir;
			history.pushState(null, null, "#" + firstSubsite);
			this.LoadSubsiteTags(firstSubsite);
		} else
			this.LoadSubsiteTags(hashSubsite);
	},
	computed: {
		canEdit() {
			return currentUser?.Level >= "4-admin";
		}
	},
	methods: {
		LoadSubsiteTags(subsite) {
			this.loading = true;
			$.get("/api/tag.php/stats/" + subsite).done(tags => {
				this.tags = tags;
				this.subsite = subsite;
			}).fail(response => {
				alert(response.responseText);
			}).always(() => {
				this.loading = false;
			});
		},
		Edit(tag) {
			if(this.canEdit) {
				this.edit = { Name: tag.Name, Description: tag.Description };
				this.$nextTick(() => {
					this.$refs.editField.focus();
					autosize(this.$refs.editField);
				});
			}
		},
		Cancel() {
			this.edit = false;
		},
		Save(tag) {
			if(this.canEdit)
				$.ajax({
					url: "/api/tag.php/description/" + this.subsite + "/" + tag.Name,
					type: "PUT",
					data: this.edit.Description
				}).done(() => {
					tag.Description = this.edit.Description;
					this.edit = false;
				}).fail(request => {
					alert(request.responseText);
				});
		}
	},
	template: /* html */ `
		<nav class=tabs>
			<a v-for="s in subsites" :class="{selected: s.dir == subsite}" :href="'#' + s.dir" :title="'tags for ' + s.name" @click=LoadSubsiteTags(s.dir)>{{s.name}}</a>
		</nav>

		<ul id=taginfo>
			<li v-for="tag in tags">
				<div class=tagdata>
					<a :href="subsite + '/' + tag.Name + '/'">{{tag.Name}}</a>
					<span class=count>{{tag.Count}} uses</span>
					<time :datetime=tag.LastUsed.DateTime>{{tag.LastUsed.Display}} ago</time>
				</div>
				<div class=description>
					<span class=editable v-html=tag.Description v-if="tag.Name != edit.Name"></span>
					<label class=multiline v-if="tag.Name == edit.Name">
						<span class=field><textarea v-model=edit.Description ref=editField></textarea></span>
						<span>
							<a href="#save" title="save tag description" class="action okay" @click.prevent=Save(tag)></a>
							<a href="#cancel" title="cancel editing" class="action cancel" @click.prevent=Cancel()></a>
						</span>
					</label>
					<a href="#edit" class="action edit" v-if="canEdit && tag.Name != edit.Name" @click.prevent=Edit(tag)></a>
				</div>
			</li>
		</ul>
	`
}).mount("div.tabbed");
