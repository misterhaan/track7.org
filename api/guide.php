<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'guide.php';

/**
 * Handler for guide API requests.
 */
class GuideApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest guides with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include guides with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of guides to skip. usually the number of guides currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'chapters', 'retrieve the chapters of a guide.');
		$endpoint->PathParameters[] = new ParameterDocumentation('guide', 'string', 'id of guide whose chapters to look up.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retreives guide content for editing.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the guide to retrieve.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'idAvailable', 'checks if a guide id is available.  this means not in use or already used by the specified guide.', 'plain text', 'send the proposed new id for the guide as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId', 'string', 'current id of the guide that might be changing its id.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'save changes to a guide.', 'json', 'form content formatted as json because of multiple chapters.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of guide to save edits.  if blank, saves a new guide.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new id for the guide.  does not need to match the old id.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'title of the guide.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('summary', 'string', 'summary of the guide in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('level', 'string', 'difficulty level of this guide:  beginner, intermediate, or advanced.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('addtags', 'string', 'comma-separated list of tag names to add to this guide.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('deltags', 'string', 'comma-separated list of tag names to remove from this guide.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('chapters', 'array', 'chapters, in order.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('chapters[].title', 'string', 'chapter title.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('chapters[].markdown', 'string', 'chapter content in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('correctionsOnly', 'boolean', 'true if the changes are simple corrections and should not mark the guide as having been updated.', false);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'publish', 'publish a guide.  only available to administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of guide to publish.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'id', 'deletes an unpublished guide.  must be logged in as the administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the guide to delete.', true);

		return $endpoints;
	}

	/**
	 * Get latest guides.
	 * @param array $params May contain number of guides to skip and/or name of tag to look for (empty array will skip 0 and retrieve all guides regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		$response = IndexGuide::List(self::RequireDatabase(), self::RequireUser(), $tagName, $skip);
		if (!$tagName && self::HasAdminSecurity())
			$response->Drafts = IndexGuide::ListDrafts(self::$db, self::$user);
		self::Success($response);
	}

	/**
	 * Get the chapters of a guide.
	 * @param array $params ID of the guide to look up
	 */
	protected static function GET_chapters(array $params): void {
		$guide = trim(array_shift($params));
		if (!$guide)
			self::NotFound('guide id must be specified.');
		self::Success(Chapter::List(self::RequireDatabase(), $guide));
	}

	protected static function GET_edit(array $params): void {
		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('guide id must be specified.');

		$guide = EditGuide::FromID(self::RequireDatabase(), $id);
		if (!$guide)
			self::NotFound('no guide found for specified id.');

		$guide->LoadChapters(self::RequireDatabase());

		self::Success($guide);
	}

	/**
	 * Check if a guide ID is available.
	 * @param array $params Current ID, if any.
	 */
	protected static function POST_idAvailable(array $params): void {
		$oldID = trim(array_shift($params));
		$newID = self::ReadRequestText();
		self::Success(EditGuide::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to a guide.
	 * @param array $params Current ID of the guide being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can edit a guide.  you might need to log in again.');

		$id = array_shift($params);
		$request = self::ReadRequestJsonObject();
		$guide = EditGuide::FromRequestObject(self::RequireDatabase(), $request, $id);

		if ($id)
			$guide->Update(self::$db, $id, $request->correctionsOnly);
		else
			$guide->Create(self::$db);
		self::Success('/guides/' . $guide->ID);
	}

	/**
	 * Publish a guide.  Only available to administrators.
	 * @param array $params Post ID to publish
	 */
	protected static function POST_publish(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can publish guides.  you might need to log in again.');

		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('guide id must be specified.');

		$guide = EditGuide::Publish(self::RequireDatabase(), $id);
		if (!$guide)
			self::NotFound('unable to locate guide for the specified id.');

		require_once 'formatUrl.php';
		self::Tweet('new guide: ' . $guide->Title, FormatURL::FullUrl('/guides/' . $guide->ID));

		self::Success();
	}

	/**
	 * Delete an unpublished guide.  Only available to administrators.
	 * @param array $params Guide ID to delete
	 */
	protected static function DELETE_id(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can publish guides.  you might need to log in again.');

		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('guide id must be specified.');

		$guide = EditGuide::Delete(self::RequireDatabase(), $id);
		if (!$guide)
			self::NotFound('unable to locate guide with the specified id.');

		self::Success();
	}
}
GuideApi::Respond();
