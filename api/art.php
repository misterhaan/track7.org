<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'art.php';

/**
 * Handler for art API requests.
 */
class ArtApi extends Api {
	private const ThumbSize = 150;
	private const FullSize = 800;

	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest art with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include art with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of art to skip. usually the number of art currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves all information for a single art.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the art to look up (unique part of the url to the art).', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'idAvailable', 'checks if an art id is available.  this means not in use or already used by the specified art.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId=newId', 'string', 'oldId is the id of the art that might be changing its id, or just start with the equal sign for a new art.  newId is the proposed new id for the art.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'saves edits to an existing art or adds a new art.  must be logged in as the administrator.', 'multipart', 'for the most part this is fields from the form, but instead of sending the list of tags it expects tags to remove and tags to add.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the art to update.  if not specified, adds a new art.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new art id (unique part of the url to the art).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'title of the art.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('description', 'string', 'description of the art in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('image', 'file (jpeg or png)', 'the actual image for this art as a jpeg or png file upload.  required for new art.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('deviation', 'string', 'unique part of the deviant art url if the art is on deviant art.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('deltags', 'string', 'comma-separated list of tag names to remove from the art.  only used when updating an existing art.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('addtags', 'string', 'comma-separated list of tag names to add to the art.');

		return $endpoints;
	}

	/**
	 * Get latest art.
	 * @param array $params May contain number of art to skip and/or name of tag to look for (empty array will skip 0 and retrieve all art regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		self::Success(IndexArt::List(self::RequireDatabase(), $tagName, $skip));
	}

	/**
	 * Get all information on an art for purposes of editing.
	 * @param array $params ID of art to edit
	 */
	protected static function GET_edit(array $params): void {
		$id = array_shift($params);
		if (!$id)
			self::NotFound('id must be specified.');
		if ($art = EditArt::FromID(self::RequireDatabase(), $id))
			self::Success($art);
		else
			self::NotFound("unable to find art “$id.”");
	}

	/**
	 * Check if an art ID is available.
	 * @param array $params Current ID followed by an equal sign followed by the new ID to check.  Currenty ID may be blank.
	 */
	protected static function GET_idAvailable($params): void {
		$oldNewID = array_shift($params);
		if (!$oldNewID)
			self::NotFound('id must be specified.');
		$oldNewID = explode('=', $oldNewID);
		if (count($oldNewID) == 1) {
			$oldID = '';
			$newID = $oldNewID[0];
		} else {
			$oldID = array_shift($oldNewID);
			$newID = implode('=', $oldNewID);
		}
		self::Success(EditArt::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to an art.
	 * @param array $params Current ID of the art being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can edit art.  you might need to log in again.');

		$id = array_shift($params);
		$art = EditArt::FromPOST();
		$result = EditArt::IdAvailable(self::$db, $id, $art->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);

		require_once 'image.php';
		$image = Image::FromUpload('image');
		if (!$id && !$image)
			self::NotFound('new art must include an image file.');
		if ($image) {
			$art->Ext = $image->GetExtension();
			$name = $_SERVER['DOCUMENT_ROOT'] . '/art/img/' . $art->ID;
			$image->SaveResized($art->Ext, ["$name-prev.$art->Ext" => self::ThumbSize, "$name.$art->Ext" => self::FullSize]);
			$image->Delete();
		}

		if ($id)
			$art->Update(self::$db, $id);
		else {
			$art->SaveNew(self::$db);
			// TODO:  migrate t7send
			require_once 't7send.php';
			require_once 'formatUrl.php';
			t7send::Tweet('new art: ' . $art->Title, FormatURL::FullUrl('/art/' . $art->ID));
		}

		if ($id && $id != $art->ID) {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/art/img/';
			if ($image) {
				unlink($path . $id . '.*');
				unlink($path . $id . '-prev.*');
			} else {
				rename($path . $id . '.' . $art->Ext, $path . $photo->ID . '.' . $art->Ext);
				rename($path . $id . '-prev.' . $art->Ext, $path . $photo->ID . '-prev.' . $art->Ext);
			}
		}
		self::Success('/art/' . $art->ID);
	}
}
ArtApi::Respond();
