<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'photo.php';

/**
 * Handler for photo API requests.
 */
class PhotoApi extends Api {
	private const ThumbSize = 150;
	private const PhotoSize = 800;

	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest photos with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('tagName', 'string', 'specify a tag name to only include photos with that tag.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of photos to skip. usually the number of photos currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves all information for a single photo.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the photo to look up (unique part of the url to the photo).', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'idAvailable', 'checks if a photo id is available.  this means not in use or already used by the specified photo.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId=newId', 'string', 'oldId is the id of the photo that might be changing its id, or just start with the equal sign for a new photo.  newId is the proposed new id for the photo.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'saves edits to an existing photo or adds a new photo.  must be logged in as the administrator.', 'multipart', 'for the most part this is fields from the form, but instead of sending the list of tags it expects tags to remove and tags to add.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the photo to update.  if not specified, adds a new photo.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new photo id (unique part of the url to the photo).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'title of the photo (also displayed as a caption).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('story', 'string', 'story of the photo in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('image', 'file (jpeg)', 'the actual image for this photo as a jpeg file upload.  required for new photos.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('youtube', 'string', 'unique part of the video url if the photo is actually a video.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('taken', 'string', 'date and time the photo was taken.  leave blank to read from exif data on the image file.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('year', 'integer', 'year the photo was taken.  leave blank to use the year from the value used for taken.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('deltags', 'string', 'comma-separated list of tag names to remove from the photo.  only used when updating an existing photo.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('addtags', 'string', 'comma-separated list of tag names to add to the photo.');

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
				$tagName = str_replace('+', ' ', trim($param));
		self::Success(IndexPhoto::List(self::RequireDatabase(), $tagName, $skip));
	}

	/**
	 * Get all information on a photo for purposes of editing.
	 * @param array $params ID of photo to edit
	 */
	protected static function GET_edit(array $params): void {
		$id = array_shift($params);
		if (!$id)
			self::NotFound('id must be specified.');
		if ($photo = EditPhoto::FromID(self::RequireDatabase(), $id)) {
			if ($photo->Taken) {
				require_once 'formatDate.php';
				$photo->TakenFormatted = FormatDate::Local('Y-m-d g:i:s a', $photo->Taken, self::RequireUser());
			}
			self::Success($photo);
		} else
			self::NotFound("unable to find photo “$id.”");
	}

	/**
	 * Check if a photo ID is available.
	 * @param array $params Current ID followed by an equal sign followed by the new ID to check.  Current ID may be blank.
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
		self::Success(EditPhoto::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to a photo.
	 * @param array $params Current ID of the photo being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can edit photos.  you might need to log in again.');

		$id = array_shift($params);
		$photo = EditPhoto::FromPOST(self::RequireUser());
		$result = EditPhoto::IdAvailable(self::$db, $id, $photo->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);
		require_once 'image.php';
		$image = Image::FromUpload('image');
		if (!$id && !$image)
			self::NotFound('new photos must include an image file.');
		if ($image) {
			$name = $_SERVER['DOCUMENT_ROOT'] . '/album/photos/' . $photo->ID;
			$image->SaveResized('jpeg', ["$name.jpg" => self::ThumbSize, "$name.jpeg" => self::PhotoSize]);
			if (!$photo->Taken) {
				$exif = $image->GetEXIF();
				if (isset($exif['EXIF']['DateTimeOriginal'])) {
					require_once 'formatDate.php';
					$photo->Taken = FormatDate::LocalToTimestamp($exif['EXIF']['DateTimeOriginal'], self::$user);
				}
			}
			$image->Delete();
		}
		if (!$photo->Year && $photo->Taken) {
			require_once 'formatDate.php';
			$photo->Year = FormatDate::Local('Y', $photo->Taken, self::$user);
		}

		if ($id)
			$photo->Update(self::$db, $id);
		else {
			$photo->SaveNew(self::$db);
			require_once 'formatUrl.php';
			self::Tweet(($photo->Youtube ? 'new video: ' : 'new photo: ') . $photo->Title, FormatURL::FullUrl('/album/' . $photo->ID));
		}

		if ($id && $id != $photo->ID) {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/album/photos/';
			if ($image) {
				unlink($path . $id . '.jpeg');
				unlink($path . $id . '.jpg');
			} else {
				rename($path . $id . '.jpeg', $path . $photo->ID . '.jpeg');
				rename($path . $id . '.jpg', $path . $photo->ID . '.jpg');
			}
		}
		self::Success('/album/' . $photo->ID);
	}
}
PhotoApi::Respond();
