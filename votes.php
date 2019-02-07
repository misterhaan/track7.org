<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true]);
$html->Open('votes');
?>
			<h1>votes</h1>

			<table id=votes>
				<tbody>
					<tr v-for="vote in votes">
						<td><span class=rating :data-stars=vote.vote></span></td>
						<td><time v-html=vote.posted.display :datetime=vote.posted.datetime :title=vote.posted.title></time></td>
						<td><img class=votetype :src="'/images/storytype/' + vote.type + '.png'"></td>
						<td><a :href=vote.url>{{vote.title}}</a></td>
<?php
if($user->IsAdmin()) {
?>
						<td v-if=vote.username><a :href="'/user/' + vote.username + '/'">{{vote.displayname || vote.username}}</a></td>
						<td v-if=!vote.username>{{vote.ip}}</td>
						<td><a class="del action" href="/api/votes/delete" v-on:click.prevent=Delete(vote)></a></td>
<?php
}
?>
					</tr>
				</tbody>
				<tfoot>
					<tr v-if=loading><td colspan=6 class=loading>loading votes . . .</td></tr>
					<tr v-if="more && !loading"><td colspan=6 class=calltoaction><a class="get action" href="/api/votes/list" v-on:click.prevent=Load>load more votes</a></td></tr>
				</tfoot>
			</table>
<?php
$html->Close();
