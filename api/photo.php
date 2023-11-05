<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'photo.php';

/**
 * Handler for photo API requests.
 */
class PhotoApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];
		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest photos with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include photos with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of photos to skip. usually the number of photos currently loaded.');
		return $endpoints;
	}

	/**
	 * Get latest photos.
	 * @param array $params May contain number of photos to skip and/or name of tag to look for (empty array will skip 0 and retrieve all photos regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		self::Success(IndexPhoto::List(self::RequireDatabase(), $tagName, $skip));
	}
}
PhotoApi::Respond();
