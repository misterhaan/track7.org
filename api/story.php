<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'story.php';

/**
 * Handler for story API requests.
 */
class StoryApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest stories with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of stories to skip. usually the number of stories currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'series', 'retrieves the latest stories with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of stories to skip. usually the number of stories currently loaded.');

		return $endpoints;
	}

	/**
	 * Get latest stories.
	 * @param array $params May contain number of stories to skip
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(IndexStory::List(self::RequireDatabase(), self::RequireUser(), $skip));
	}

	/**
	 * Get stories in a series.
	 * @param array $params ID of series to look up
	 */
	protected static function GET_series(array $params): void {
		$series = array_shift($params);
		self::Success(IndexStory::ListSeries(self::RequireDatabase(), self::RequireUser(), $series));
	}
}
StoryApi::Respond();
