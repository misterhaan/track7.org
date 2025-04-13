<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'activity.php';

/**
 * Handler for activity API requests.
 */
class ActivityApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves all site activity with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of activities to skip. usually the number of activities currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'byuser', 'retrieves site activity by the specified user with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('user', 'integer', 'specify a user id to only return activity from that user.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of activities to skip. usually the number of activities currently loaded.');

		return $endpoints;
	}

	/**
	 * get latest site activity.
	 * @param array $params may contain number of activities to skip
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(SiteActivity::List(self::RequireDatabase(), self::RequireUser(), $skip));
	}

	/**
	 * get latest user activity.
	 * @param array $params id of user to look up and may contain number of activities to skip
	 */
	protected static function GET_byuser(array $params): void {
		$username = trim(array_shift($params));
		$skip = +array_shift($params);
		self::Success(UserActivity::List(self::RequireDatabase(), self::RequireUser(), $username, $skip));
	}
}
ActivityApi::Respond();
