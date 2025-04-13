<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'user.php';

/**
 * Handler for user API requests.
 */
class UserApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves all site users.');

		$endpoints[] = $endpoint = new EndpointDocumentation('PUT', 'friend', 'adds a user as a friend for the logged-in user.  requires authentication.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'user id of the friend to add.');

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'friend', 'removes a user from friends list of the logged-in user.  requires authentication.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'user id of the friend to remove.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'logout', 'logs out the current user.');

		return $endpoints;
	}

	/**
	 * get latest site activity.
	 * @param array $params may contain number of activities to skip
	 */
	protected static function GET_list(array $params): void {
		self::Success(DetailedUser::List(self::RequireDatabase(), self::RequireUser()));
	}

	protected static function PUT_friend(array $params): void {
		$friend = +array_shift($params);
		if (!$friend)
			self::NotFound('friend id must be specified.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be logged in to add a friend.');
		if (self::$user->ID == $friend)
			self::Forbidden('you cannot add yourself as a friend.');
		self::Success(Friend::Add(self::RequireDatabase(), self::RequireUser(), $friend));
	}

	protected static function DELETE_friend(array $params): void {
		$friend = +array_shift($params);
		if (!$friend)
			self::NotFound('friend id must be specified.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be logged in to remove a friend.');
		self::Success(Friend::Remove(self::RequireDatabase(), self::RequireUser(), $friend));
	}

	protected static function POST_logout(array $params): void {
		if (!self::IsUserLoggedIn())
			self::Success();  // no errors for logging out of stale login session
		self::$user->Logout(self::RequireDatabase());
		self::Success();
	}
}
UserApi::Respond();
