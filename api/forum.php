<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'forum.php';

/**
 * Handler for forum API requests.
 */
class ForumApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest forum discussions with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include discussions with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of discussions to skip. usually the number of discussions currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'start', 'start a new discussion.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('name', 'string', 'name of the author. anonymous if blank or not provided.  ignored if discussion started by a logged-in user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('contact', 'string', 'contact url or e-mail address of the author.  no contact link if blank or not provided.  ignored if discussion started by a logged-in user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'discussion title.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('tags', 'string', 'list of tags for this discussion.  comma-separated.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('message', 'string', 'discussion content in markdown format.', true);

		return $endpoints;
	}

	/**
	 * Get latest discussions.
	 * @param array $params May contain number of discussions to skip and/or name of tag to look for (empty array will skip 0 and retrieve all discussions regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		$response = IndexDiscussion::List(self::RequireDatabase(), self::RequireUser(), $tagName, $skip);
		self::Success($response);
	}

	/**
	 * Start a new discussion.
	 */
	protected static function POST_start(): void {
		$discussion = Discussion::FromPOST();
		$message = trim($_POST['message']);
		$name = $contact = '';
		if (!self::IsUserLoggedIn()) {
			$name = isset($_POST['name']) && trim($_POST['name']) ? trim($_POST['name']) : User::DefaultName;
			$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
		}
		$discussion->Start(self::RequireDatabase(), self::$user, $name, $contact, $message);
		self::Success($discussion->ID);
	}
}
ForumApi::Respond();
