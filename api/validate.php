<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for validate api requests.
 * @author misterhaan
 */
class validateApi extends t7api {
	/**
	 * write out the documentation for the validate api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=getblogurl>get blogurl</h2>
			<p>check if a url is available for a blog entry.</p>
			<dl class=parameters>
				<dt>value</dt>
				<dd>url to check.</dd>
				<dt>id</dt>
				<dd>
					id of blog entry that wants to use the url.  optional; assumes new
					entry.
				</dd>
			</dl>

			<h2 id=getdatetime>get datetime</h2>
			<p>
				make sure a datetime entry can be understood and format it to show so.
			</p>
			<dl class=parameters>
				<dt>value</dt>
				<dd>datetime to check.</dd>
			</dl>

			<h2 id=getblogurl>get photourl</h2>
			<p>check if a url is available for a photo.</p>
			<dl class=parameters>
				<dt>value</dt>
				<dd>url to check.</dd>
				<dt>id</dt>
				<dd>
					id of photo that wants to use the url.  optional; assumes new photo.
				</dd>
			</dl>

<?php
	}

	/**
	 * check availability of a url for a blog entry.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function blogurlAction($ajax) {
		self::ValidateUrl('blog_entries', 'title', $ajax);
	}

	/**
	 * check availability of a url for a photo.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function photourlAction($ajax) {
		self::ValidateUrl('photos', 'caption', $ajax);
	}

	/**
	 * check availability of a url for an item.
	 * @param string $table name of table to check for url.
	 * @param string $namecol name of column in $table that stores the name of the item.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	private static function ValidateUrl($table, $namecol, $ajax) {
		if(isset($_GET['value']) && trim($_GET['value'])) {
			$id = isset($_GET['id']) ? +$_GET['id'] : 0;
			self::CheckUrl($table, $namecol, trim($_GET['value']), $id, $ajax);
		} else
			$ajax->Fail('url is required');
	}
}
validateApi::Respond();
