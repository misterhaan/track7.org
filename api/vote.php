<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once dirname(__DIR__) . '/etc/class/vote.php';

/**
 * Handler for vote API requests.
 */
class VoteApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'cast', 'casts a vote.  replaces existing vote if the user (or ip address for anonymous votes) already has a vote on this post.', 'post', '');
		$endpoint->PathParameters[] = new ParameterDocumentation('post', 'integer', 'specify the post being voted on.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('vote', 'integer', 'vote being cast:  1 through 5.', true);

		return $endpoints;
	}

	/**
	 * Cast a vote.
	 * @param array $params Post ID being voted on
	 */
	public static function POST_cast(array $params): void {
		$post = +array_shift($params);
		if (!$post)
			self::NotFound('post must be specified.');
		if (!isset($_POST['vote']) || !is_numeric($_POST['vote']))
			self::NotFound('vote must be specified and numeric.');
		$vote = +$_POST['vote'];
		if ($vote < 1 || $vote > 5)
			self::DetailedError('vote must be from 1 to 5.');

		VoteRating::Cast(self::RequireDatabase(), self::RequireUser(), $post, $vote);
		self::Success(new VoteRating(self::$db, $post));
	}
}
VoteApi::Respond();
