<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'tag.php';

/**
 * API handler for tags.
 */
class TagApi extends Api {
	/**
	 * write out the documentation for the photo api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
		<h2 id=getlist>GET list/{subsite}</h2>
		<p>retrieves the tags used by the specified subsite, in order of most-recently used.</p>
		<dl class=parameters>
			<dt>{subsite}</dt>
			<dd>specify the subsite to list tags for. required, string.</dd>
		</dl>

		<h2 id=putdescription>PUT description/{subsite}/{tagname}</h2>
		<p>updates the description of a tag.</p>
		<dl class=parameters>
			<dt>{subsite}</dt>
			<dd>specify the subsite whose tag will update</dd>
			<dt>{tagname}</dt>
			<dd>specify the tag name to update</dd>
		</dl>
<?php
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
		self::RequireDatabase();
		$description = self::ReadRequestText();
		self::Success(Tag::UpdateDescription(self::$db, $subsite, $name, $description));
	}
}
TagApi::Respond();
