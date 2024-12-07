import "jquery";
import { createApp } from "vue";
import { GetCurrentUser } from "user";

const user = GetCurrentUser();

createApp({
	name: "Votes",
	data() {
		return {
			votes: [],
			error: null,
			loading: false,
			hasMore: false
		};
	},
	computed: {
		canDelete() {
			return user && user.Level >= "4-admin";
		}
	},
	created: function() {
		this.Load();
	},
	methods: {
		Load() {
			this.loading = true;
			$.get("/api/vote.php/list/" + this.votes.length).done(result => {
				this.votes = this.votes.concat(result.Votes);
				this.hasMore = result.HasMore;
			}).fail(request => {
				alert(request.responseMessage);
			}).always(() => {
				this.loading = false;
			});
		},
		Delete(vote) {
			$.post("/api/vote.php/delete/" + vote.ID).done(result => {
				const v = this.votes.indexOf(vote);
				if(v > -1)
					this.votes.splice(v, 1);
			}).fail(request => {
				this.error = request.responseMessage;
			});
		}
	},
	template: /* html */ `
		<p class=error v-if=error>{{error}}</p>

		<table id=votes>
			<tbody>
				<tr v-for="vote in votes">
					<td><span class=rating :data-stars=vote.Vote></span></td>
					<td><time v-html=vote.Instant.Display :datetime=vote.Instant.DateTime :title=vote.Instant.Tooltip></time></td>
					<td><a class=votepost :href=vote.URL>
						<img class=votetype :src="'/images/storytype/' + vote.Subsite + '.png'">
						{{vote.Title}}
					</a></td>
					<td v-if=vote.Username><a :href="'/user/' + vote.Username + '/'">{{vote.DisplayName}}</a></td>
					<td v-if=!vote.Username>{{vote.IP}}</td>
					<td v-if=canDelete><a class="del action" href="/api/vote.php/delete" v-on:click.prevent=Delete(vote)></a></td>
				</tr>
			</tbody>
		</table>

		<p v-if=loading class=loading>loading votes . . .</p>
		<p v-if="hasMore && !loading" class=calltoaction><a class="get action" href="/api/vote/list" @click.prevent=Load>load more votes</a></p>
	`
}).mount("#votes");
