<?
define('MAX_VOTE_GET', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'cast':
			if(isset($_POST['type']) && isset($_POST['key']) && isset($_POST['vote']))
				switch($_POST['type']) {
					case 'art':
					case 'guide':
					case 'lego':
						if($db->real_query('insert into ' . VoteTableName($_POST['type']) . ' (' . VoteColumnName($_POST['type']) . ', voter, ip, vote, posted) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . ($user->IsLoggedIn() ? +$user->ID : 0) . '\', ' . ($user->IsLoggedIn() ? 0 : 'inet_aton(\'' . $_SERVER['REMOTE_ADDR'] . '\')') . ', \'' . +$_POST['vote'] . '\', \'' . +time() . '\') on duplicate key update vote=\'' . +$_POST['vote'] . '\', posted=\'' . +time() .'\'')) {
							$ajax->Data->vote = +$_POST['vote'];
							if($db->real_query('update ' . RatingTableName($_POST['type']) . ' set rating=(select round((sum(vote)+3)/(count(vote)+1), 2) from ' . VoteTableName($_POST['type']) . ' where ' . VoteColumnName($_POST['type']) . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . VoteColumnName($_POST['type']) . '), votes=(select count(vote) from ' . VoteTableName($_POST['type']) . ' where ' . VoteColumnName($_POST['type']) . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . VoteColumnName($_POST['type']) . ') where id=\'' . $db->escape_string($_POST['key']) . '\'')) {
								if($gi = $db->query('select rating, votes from ' . RatingTableName($_POST['type']) . ' where id=\'' . $db->escape_string($_POST['key']) . '\' limit 1'))
									if($gi = $gi->fetch_object()) {
										$ajax->Data->rating = +$gi->rating;
										$ajax->Data->votes = +$gi->votes;
									}
							}
						} else
							$ajax->Fail('error recording your rating.');
						break;
					default:
						$ajax->Fail('unknown type.  known types are:  art, guide, lego.');
				}
			else
				$ajax->Fail('missing at least one required parameter:  type, key, and vote.');
			break;
		case 'delete':
			if($user->IsAdmin())
				if(isset($_POST['type']) && isset($_POST['id']) && isset($_POST['item']) && $_POST['type'] && +$_POST['id'] && $_POST['item'])
					switch($_POST['type']) {
						case 'art':
						case 'guide':
						case 'lego':
							if($db->query('delete from ' . VoteTableName($_POST['type']) . ' where id=\'' . +$_POST['id'] . '\'')) {
								$ajax->Data->deleted = $db->affected_rows;
								$db->real_query('update ' . RatingTableName($_POST['type']) . ' set rating=(select round((sum(vote)+3)/(count(vote)+1), 2) from ' . VoteTableName($_POST['type']) . ' where ' . VoteColumnName($_POST['type']) . '=\'' . $db->escape_string($_POST['item']) . '\' group by ' . VoteColumnName($_POST['type']) . '), votes=(select count(vote) from ' . VoteTableName($_POST['type']) . ' where ' . VoteColumnName($_POST['type']) . '=\'' . $db->escape_string($_POST['item']) . '\' group by ' . VoteColumnName($_POST['type']) . ') where id=\'' . $db->escape_string($_POST['item']) . '\'');
							} else
								$ajax->Fail('error deleting vote:  ' . $db->error);
							break;
						default:
							$ajax->Fail('unknown type.  known types are:  art, guide, lego.');
							break;
					}
				else
					$ajax->Fail('vote type, id, and item are required.');
			else
				$ajax->Fail('votes may only be deleted by the administrator.');
			break;
		case 'list':
			$extracols = $user->IsAdmin() ? ' u.username, u.displayname, inet_ntoa(v.ip) as ip, v.VOTE_COLUMN as item,' : '';
			$extrajoins = $user->IsAdmin() ? ' left join users as u on u.id=v.voter' : '';
			$oldest = isset($_GET['oldest']) && $_GET['oldest'] ? +$_GET['oldest'] : time() + 43200;
			if($votes = $db->query('select \'art\' as type, v.id,' . str_replace('VOTE_COLUMN', 'art', $extracols) . ' v.vote, v.posted, a.title, concat(\'/art/\', a.url) as url from art_votes as v' . $extrajoins . ' left join art as a on v.art=a.id where v.posted<\'' . $oldest . '\' union '
					. 'select \'guide\' as type, v.id,' . str_replace('VOTE_COLUMN', 'guide', $extracols) . ' v.vote, v.posted, g.title, concat(\'/guides/\', g.url, \'/1\') as url from guide_votes as v' . $extrajoins . ' left join guides as g on v.guide=g.id where v.posted<\'' . $oldest . '\' union '
					. 'select \'lego\' as type, v.id,' . str_replace('VOTE_COLUMN', 'lego', $extracols) . ' v.vote, v.posted, l.title, concat(\'/lego/\', l.url) as url from lego_votes as v' . $extrajoins . ' left join lego_models as l on v.lego=l.id where v.posted<\'' . $oldest . '\' order by posted desc limit ' . MAX_VOTE_GET)) {
				$ajax->Data->votes = [];
				$ajax->Data->oldest = 0;
				while($vote = $votes->fetch_object()) {
					$ajax->Data->oldest = +$vote->posted;
					$vote->posted = t7format::TimeTag('smart', $vote->posted, 'g:i a \o\n l F jS Y');
					$ajax->Data->votes[] = $vote;
				}
				if($more = $db->query('select (select count(1) from art_votes where posted<\'' . $ajax->Data->oldest . '\')+(select count(1) from guide_votes where posted<\'' . $ajax->Data->oldest . '\')+(select count(1) from lego_votes where posted<\'' . $ajax->Data->oldest . '\') as num'))
					if($more = $more->fetch_object())
						$ajax->Data->more = +$more->num;
			} else
				$ajax->Fail('error looking up votes:  ' . $db->error);
			break;
		default:
			$ajax->Fail('unknown function name.  supported function names are: cast, delete, list.');
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true]);
$html->Open('votes');
?>
			<h1>votes</h1>

			<table id=votes>
				<tbody data-bind="foreach: votes">
					<tr>
						<td><span class=rating data-bind="attr: {'data-stars': vote}"></span></td>
						<td><time data-bind="html: posted.display, attr: {datetime: posted.datetime, title: posted.title}"></time></td>
						<td><img class=votetype data-bind="attr: {src: '/images/storytype/' + type + '.png'}"></td>
						<td><a data-bind="text: title, attr: {href: url}"></a></td>
<?php
if($user->IsAdmin()) {
?>
						<!-- ko if: username -->
						<td><a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a></td>
						<!-- /ko -->
						<!-- ko ifnot: username -->
						<td data-bind="text: ip"></td>
						<!-- /ko -->
						<td><a class="del action" href="?ajax=delete" data-bind="click: $root.Delete"></a></td>
<?php
}
?>
					</tr>
				</tbody>
				<tfoot>
					<tr data-bind="visible: loading"><td colspan=6 class=loading>loading votes</td></tr>
					<tr data-bind="visible: more() && !loading()"><td colspan=6 class=calltoaction><a class="get action" href="?ajax=list" data-bind="click: Load">load more votes</a></td></tr>
				</tfoot>
			</table>
<?php
$html->Close();

function VoteTableName($type) {
	switch($type) {
		default:
			return $type . '_votes';
	}
}

function VoteColumnName($type) {
	switch($type) {
		default:
			return $type;
	}
}

function RatingTableName($type) {
	switch($type) {
		case 'guide':
			return 'guides';
		case 'lego':
			return 'lego_models';
		default:
			return $type;
	}
}
