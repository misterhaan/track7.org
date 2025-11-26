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

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'get a list of the latest votes.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of votes to skip. usually the number of votes currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'cast', 'casts a vote.  replaces existing vote if the user (or ip address for anonymous votes) already has a vote on this post.', 'post', '');
		$endpoint->PathParameters[] = new ParameterDocumentation('post', 'integer', 'specify the post being voted on.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('vote', 'integer', 'vote being cast:  1 through 5.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'delete', 'delete a vote that was cast as spam or by a bot.  only available to administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('vote', 'string', 'id of vote to delete.  this comes from the vote list api endpoint and contains 2 semicolons.', true);

		return $endpoints;
	}

	/**
	 * Get latest votes.
	 * @param array $params May contain number of votes to skip (empty array will skip 0)
	 */
	public static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(Vote::List(self::RequireDatabase(), self::RequireUser(), $skip));
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

		Vote::Cast(self::RequireDatabase(), self::RequireUser(), $post, $vote);
		self::Success(Rating::FromPostID(self::$db, $post));
	}

	/**
	 * Delete a vote.
	 * @param array $params Vote ID to delete
	 */
	public static function POST_delete(array $params): void {
		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('vote id must be specified');
		Vote::Delete(self::RequireDatabase(), $id);
		self::Success();
	}
}
VoteApi::Respond();
