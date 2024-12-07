<?php
class Rating {
	public $Rating;
	public $VoteCount;

	private function __construct(float $rating, int $voteCount) {
		$this->Rating = $rating;
		$this->VoteCount = $voteCount;
	}

	public static function FromPostID(mysqli $db, int $post): self {
		$select = $db->prepare('select rating, votecount from rating where post=?');
		$select->bind_param('i', $post);
		$select->execute();
		$select->bind_result($rating, $voteCount);
		$select->fetch();
		return new self($rating, $voteCount);
	}
}

class Vote {
	private const ListLimit = 24;

	public string $ID;
	public int $Vote;
	public TimeTagData $Instant;
	public string $URL;
	public string $Subsite;
	public string $Title;
	public string $Username;
	public string $DisplayName;
	public string $IP;

	private function __construct(CurrentUser $user, string $id, int $vote, int $instant, string $url, string $subsite, $title, ?string $username, ?string $displayName, string $ip) {
		$this->ID = $id;
		$this->Vote = $vote;
		require_once 'formatDate.php';
		$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->URL = $url;
		$this->Subsite = $subsite;
		$this->Title = $title;
		$this->Username = $username ? $username : '';
		$this->DisplayName = $displayName ? $displayName : $this->Username;
		$this->IP = $ip;
	}

	public static function List(mysqli $db, CurrentUser $user, int $skip): VoteList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select concat_ws(\';\', p.id, v.user, v.ip) as id, v.vote, unix_timestamp(v.instant), p.url, p.subsite, p.title, u.username, u.displayname, if(v.ip=0,\'\',inet_ntoa(v.ip)) from vote as v left join post as p on p.id=v.post left join user as u on u.id=v.user order by v.instant desc limit ? offset ?');
			$select->bind_param('ii', $limit, $skip);
			$select->execute();
			$select->bind_result($id, $vote, $instant, $url, $subsite, $title, $username, $displayname, $ip);
			$result = new VoteList();
			while ($select->fetch())
				if (count($result->Votes) < self::ListLimit)
					$result->Votes[] = new self($user, $id, $vote, $instant, $url, $subsite, $title, $username, $displayname, $ip);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up votes', $mse);
		}
	}

	public static function Cast(mysqli $db, CurrentUser $user, int $post, int $vote): void {
		$ip = $user->IsLoggedIn() ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
		try {
			$replace = $db->prepare('replace into vote (post, user, ip, instant, vote) values (?, ?, inet_aton(?), now(), ?)');
			$replace->bind_param('iisi', $post, $user->ID, $ip, $vote);
			$replace->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error casting vote', $mse);
		}
	}

	public static function Delete(mysqli $db, string $id): void {
		$id = explode(';', $id);
		$post = +array_shift($id);
		$user = +array_shift($id);
		$ip = +array_shift($id);
		if (!$post || $user && $ip || !$user && !$ip)
			throw new DetailedException('vote id must have a post id and either a user id or an ip');

		try {
			$delete = $db->prepare('delete from vote where post=? and user=? and ip=? limit 1');
			$delete->bind_param('iii', $post, $user, $ip);
			$delete->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error deleting vote', $mse);
		}
	}
}

class VoteList {
	public $Votes = [];
	public $HasMore = false;
}
