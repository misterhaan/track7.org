<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for votes api requests.
 * @author misterhaan
 */
class votesApi extends t7api {
	const MAX_VOTE_GET = 24;
	const VoteTypes = ['art', 'guide', 'lego'];

	/**
	 * write out the documentation for the votes api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
		<h2 id=postcast>post cast</h2>
		<p>casts a vote.</p>

		<h2 id=postdelete>post delete</h2>
		<p>delete a vote that was cast as spam. admin-only.</p>

		<h2 id=getlist>get list</h2>
		<p>get a list of the latest votes.</p>

<?php
	}

	/**
	 * cast a vote.  if the voter already voted on the item, that vote is replaced.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function castAction($ajax) {
		global $db, $user;
		if (isset($_POST['type']) && isset($_POST['key']) && isset($_POST['vote']))
			if (in_array($_POST['type'], self::VoteTypes)) {
				$votetable = self::VoteTableName($_POST['type']);
				$votecolumn = self::VoteColumnName($_POST['type']);
				$ratingtable = self::RatingTableName($_POST['type']);
				if ($db->real_query('insert into ' . $votetable . ' (' . $votecolumn . ', voter, ip, vote, posted) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . ($user->IsLoggedIn() ? +$user->ID : 0) . '\', ' . ($user->IsLoggedIn() ? 0 : 'inet_aton(\'' . $_SERVER['REMOTE_ADDR'] . '\')') . ', \'' . +$_POST['vote'] . '\', \'' . +time() . '\') on duplicate key update vote=\'' . +$_POST['vote'] . '\', posted=\'' . +time() . '\'')) {
					$ajax->Data->vote = +$_POST['vote'];
					if ($db->real_query('update ' . $ratingtable . ' set rating=(select round((sum(vote)+3)/(count(vote)+1), 2) from ' . $votetable . ' where ' . $votecolumn . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . $votecolumn . '), votes=(select count(vote) from ' . $votetable . ' where ' . $votecolumn . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . $votecolumn . ') where id=\'' . $db->escape_string($_POST['key']) . '\'')) {
						if ($gi = $db->query('select rating, votes from ' . $ratingtable . ' where id=\'' . $db->escape_string($_POST['key']) . '\' limit 1'))
							if ($gi = $gi->fetch_object()) {
								$ajax->Data->rating = +$gi->rating;
								$ajax->Data->votes = +$gi->votes;
							}
					}
				} else
					$ajax->Fail('error recording your rating', $db->errno . ' ' . $db->error);
			} else
				$ajax->Fail('unknown type.  known types are:  ' . implode(', ', self::VoteTypes) . '.');
		else
			$ajax->Fail('missing at least one required parameter:  type, key, and vote.');
	}

	/**
	 * delete a vote.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function deleteAction($ajax) {
		global $db, $user;
		if ($user->IsAdmin())
			if (isset($_POST['type']) && isset($_POST['id']) && $_POST['type'] && +$_POST['id'])
				if ($_POST['type'] == 'art') {
					$id = explode(';', trim($_POST['id']));
					if ($delete = $db->prepare('delete from vote where post=? and user=? and ip=? limit 1'))
						if ($delete->bind_param('iii', $id[0], $id[1], $id[2]))
							if ($delete->execute())
								$ajax->Data->deleted = $delete->affected_rows;
							else
								$ajax->Fail('error deleting vote');
						else
							$ajax->Fail('error binding parameters to delete vote');
					else
						$ajax->Fail('error preparing to delete vote');
				} elseif (isset($_POST['item']) && $_POST['item'])
					if (in_array($_POST['type'], self::VoteTypes)) {
						$votetable = self::VoteTableName($_POST['type']);
						$votecolumn = self::VoteColumnName($_POST['type']);
						$ratingtable = self::RatingTableName($_POST['type']);
						if ($db->query('delete from ' . $votetable . ' where id=\'' . +$_POST['id'] . '\'')) {
							$ajax->Data->deleted = $db->affected_rows;
							$db->real_query('update ' . $ratingtable . ' set rating=(select round((sum(vote)+3)/(count(vote)+1), 2) from ' . $votetable . ' where ' . $votecolumn . '=\'' . $db->escape_string($_POST['item']) . '\' group by ' . $votecolumn . '), votes=(select count(vote) from ' . $votetable . ' where ' . $votecolumn . '=\'' . $db->escape_string($_POST['item']) . '\' group by ' . $votecolumn . ') where id=\'' . $db->escape_string($_POST['item']) . '\'');
						} else
							$ajax->Fail('error deleting vote', $db->errno . ' ' . $db->error);
					} else
						$ajax->Fail('unknown type.  known types are:  ' . implode(', ', self::VoteTypes) . '.');
				else
					$ajax->Fail('vote item is required.');
			else
				$ajax->Fail('vote type and id are required.');
		else
			$ajax->Fail('votes may only be deleted by the administrator.');
	}

	/**
	 * list the latest votes.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db, $user;
		$sql = 'select p.subsite as type, concat_ws(\';\', p.id, v.user, v.ip) as id,';
		$extracols = $extrajoins = '';
		if ($user->IsAdmin()) {
			$extracols = ' u.username, u.displayname, inet_ntoa(v.ip) as ip, v.VOTE_COLUMN as item,';
			$sql .= ' u.username, u.displayname, inet_ntoa(v.ip) as ip, \'\' as item,';
		}
		$sql .= ' v.vote, unix_timestamp(v.instant) as posted, p.title, p.url from vote as v left join post as p on p.id=v.post';
		if ($user->IsAdmin()) {
			$extrajoins = ' left join users as u on u.id=v.voter';
			$sql .= ' left join user as u on u.id=v.user';
		}
		$oldest = isset($_GET['oldest']) && $_GET['oldest'] ? +$_GET['oldest'] : time() + 43200;
		if ($votes = $db->query($sql . ' where unix_timestamp(v.instant)<\'' . $oldest . '\' union '
			. 'select \'lego\' as type, v.id,' . str_replace('VOTE_COLUMN', 'lego', $extracols) . ' v.vote, v.posted, l.title, concat(\'/lego/\', l.url) as url from lego_votes as v' . $extrajoins . ' left join lego_models as l on v.lego=l.id where v.posted<\'' . $oldest . '\' order by posted desc limit ' . self::MAX_VOTE_GET)) {
			$ajax->Data->votes = [];
			$ajax->Data->oldest = 0;
			while ($vote = $votes->fetch_object()) {
				$ajax->Data->oldest = +$vote->posted;
				$vote->posted = t7format::TimeTag('smart', $vote->posted, t7format::DATE_LONG);
				$ajax->Data->votes[] = $vote;
			}
			if ($more = $db->query('select (select count(1) from vote where unix_timestamp(instant)<\'' . $ajax->Data->oldest . '\')+(select count(1) from lego_votes where posted<\'' . $ajax->Data->oldest . '\') as num'))
				if ($more = $more->fetch_object())
					$ajax->Data->more = +$more->num;
		} else
			$ajax->Fail('error looking up votes:  ' . $db->error);
	}

	/**
	 * Get the name of the vote table for the requested type.
	 * @param string $type vote type (a value in self::VoteTypes)
	 * @return string name of vote table, ready for inclusion in a query
	 */
	private static function VoteTableName($type) {
		switch ($type) {
			default:
				return $type . '_votes';
		}
	}

	/**
	 * Get the name of the vote column for the requested type.
	 * @param string $type vote type (a value in self::VoteTypes)
	 * @return string name of the vote column, ready for inclusion in a query
	 */
	private static function VoteColumnName($type) {
		switch ($type) {
			default:
				return $type;
		}
	}

	/**
	 * Get the name of the rating table for the requested type.
	 * @param string $type vote type (a value in self::VoteTypes)
	 * @return string name of the rating table, ready for inclusion in a query
	 */
	private static function RatingTableName($type) {
		switch ($type) {
			case 'guide':
				return 'guides';
			case 'lego':
				return 'lego_models';
			default:
				return $type;
		}
	}
}
votesApi::Respond();
