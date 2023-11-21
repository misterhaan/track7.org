<?php
class VoteRating {
	public $Rating;
	public $VoteCount;

	public function __construct(mysqli $db, int $post) {
		$select = $db->prepare('select rating, votecount from rating where post=?');
		$select->bind_param('i', $post);
		$select->execute();
		$select->bind_result($this->Rating, $this->VoteCount);
		$select->fetch();
	}

	public static function Cast(mysqli $db, CurrentUser $user, int $post, int $vote) {
		$ip = $user->IsLoggedIn() ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
		$replace = $db->prepare('replace into vote (post, user, ip, instant, vote) values (?, ?, inet_aton(?), now(), ?)');
		$replace->bind_param('iisi', $post, $user->ID, $ip, $vote);
		$replace->execute();
	}
}
