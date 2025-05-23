<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'blog.php';

/**
 * Handler for blog API requests.
 */
class BlogApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest blog entries with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include entries with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of entries to skip. usually the number of entries currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves a blog entry for editing.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the blog entry to edit.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'idAvailable', 'checks if a blog entry id is available.  this means not in use or already used by the specified entry.', 'plain text', 'send the proposed new id for the blog entry as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId', 'string', 'curent id of the entry that might be changing its id.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'saves a blog entry.  must be logged in as the administrator.', 'multipart');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the entry that’s being saved, or blank to save a new entry.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new id of the blog entry.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'title of the blog entry.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('markdown', 'string', 'blog entry content in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('addtags', 'string', 'comma-separated list of tags to add to the blog entry.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('deltags', 'string', 'comma-separated list of tags to remove from the blog entry.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'publish', 'publishes a blog entry.  must be logged in as the administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('post', 'integer', 'specify the post id of the blog entry to publish.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'entry', 'deletes a blog entry.  must be logged in as the administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the blog entry to delete.', true);

		return $endpoints;
	}

	/**
	 * Get latest entries.
	 * @param array $params May contain number of entries to skip and/or name of tag to look for (empty array will skip 0 and retrieve all entries regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		$response = IndexBlog::List(self::RequireDatabase(), self::RequireUser(), $tagName, $skip);
		if (!$tagName && self::HasAdminSecurity())
			$response->Drafts = IndexBlog::ListDrafts(self::$db, self::$user);
		self::Success($response);
	}

	/**
	 * Get all information on a blog entry for purposes of editing.
	 * @param array $params ID of blog entry to edit
	 */
	protected static function GET_edit(array $params): void {
		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('blog entry id must be specified.');
		if ($entry = EditBlog::FromID(self::RequireDatabase(), $id))
			self::Success($entry);
		else
			self::NotFound('could not find blog entry with the specified id.');
	}

	/**
	 * Check if a blog entry ID is available.
	 * @param array $params Current ID, if any.
	 */
	protected static function POST_idAvailable($params): void {
		$oldID = trim(array_shift($params));
		$newID = self::ReadRequestText();
		self::Success(EditBlog::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to a blog entry.
	 * @param array $params Current ID of the blog entry being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can edit a blog entry.  you might need to log in again.');

		$id = trim(array_shift($params));
		$entry = EditBlog::FromPOST();
		$result = EditBlog::IdAvailable(self::$db, $id, $entry->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);

		if ($id)
			$entry->Update(self::$db, $id);
		else
			$entry->SaveNew(self::$db);
		self::Success('/bln/' . $entry->ID);
	}

	/**
	 * Publish a blog entry.  Only available to administrators.
	 * @param array $params Post ID to publish
	 */
	protected static function POST_publish(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can publish blogs.  you might need to log in again.');

		$post = +array_shift($params);
		if (!$post)
			self::NotFound('post id must be specified.');

		$entry = EditBlog::Publish(self::RequireDatabase(), $post);
		if (!$entry)
			self::NotFound('unable to locate blog entry for the specified post id.');

		require_once 'formatUrl.php';
		self::Tweet('new blog: ' . $entry->Title, FormatURL::FullUrl('/bln/' . $entry->ID));

		self::Success();
	}

	/**
	 * Delete an unpublished blog entry.  Only available to administrators.
	 * @param array $params Blog entry ID to delete
	 */
	protected static function DELETE_entry(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can delete blogs.  you might need to log in again.');

		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('entry id must be specified.');

		$entry = EditBlog::Delete(self::RequireDatabase(), $id);
		if (!$entry)
			self::NotFound('unable to locate blog entry with the specified id.');

		self::Success();
	}
}
BlogApi::Respond();
