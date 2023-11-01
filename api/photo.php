<?php
require_once dirname(__DIR__) . '/etc/class/api.php';

/**
 * handler for photo api requests.
 */
class PhotoApi extends Api {
	/**
	 * write out the documentation for the photo api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation(): void {
?>
		<h2 id=getlist>GET list[/{tagName}][/{skip}]</h2>
		<p>retrieves the lastest photos with most recent first.</p>
		<dl class=parameters>
			<dt>{tagName}</dt>
			<dd>specify a tag name to only include photos with that tag. optional, string.</dd>
			<dt>{skip}</dt>
			<dd>specify a number of photos to skip. usually the number of photos currently loaded. optional, integer.</dd>
		</dl>

<?php
	}

	/**
	 * Get latest photos.
	 * @param array $params May contain number of photos to skip and/or name of tag to look for (empty array will skip 0 and retrieve all photos regardless of tags)
	 */
	protected static function GET_list(array $params): void {
		require_once 'photo.php';
		$db = self::RequireDatabase();

		$tagName = '';
		$skip = 0;
		foreach ($params as $param)
			if (is_numeric($param))
				$skip = +$param;
			else if ($param)
				$tagName = trim($param);
		self::Success(IndexPhoto::List($db, $tagName, $skip));
	}
}
PhotoApi::Respond();
