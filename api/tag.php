<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'tag.php';

/**
 * Handler for tag API requests.
 */
class TagApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the tags used by the specified subsite, in order of most-recently used.');
		$endpoint->PathParameters[] = new ParameterDocumentation('subsite', 'string', 'specify the subsite to list tags for.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('PUT', 'description', 'updates the description of a tag to the html provided as the request body.  only available to administrators.');
		$endpoint->PathParameters[] = new ParameterDocumentation('subsite', 'string', 'specify the subsite whose tag will update.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify the tag name to update.', true);

		return $endpoints;
	}

	/**
	 * Get tags used by a subsite.
	 * @param array $params Subsite name (required) as first param
	 */
	protected static function GET_list(array $params): void {
		$subsite = array_shift($params);
		if (!$subsite)
			self::NotFound('subsite must be specified.');
		self::RequireDatabase();
		self::Success(TagFrequency::List(self::$db, $subsite));
	}

	/**
	 * Update the description of a tag.
	 * @param array $params Subsite name (required) as first param and tag name (required) as second param
	 */
	protected static function PUT_description(array $params): void {
		$subsite = array_shift($params);
		if (!$subsite)
			self::NotFound('subsite must be specified.');
		$name = array_shift($params);
		if (!$name)
			self::NotFound('name must be specified.');
		if (!self::HasAdminSecurity())
			self::Forbidden('only administrators can update tag descriptions.');
		self::RequireDatabase();
		$description = self::ReadRequestText();
		self::Success(ActiveTag::UpdateDescription(self::$db, $subsite, $name, $description));
	}
}
TagApi::Respond();
