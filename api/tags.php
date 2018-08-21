<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for tags api requests.
 * @author misterhaan
 */
class tagsApi extends t7api {
	const MAXENTRIES = 9;

	private static $AllowedTypes = ['art', 'blog', 'forum', 'guide', 'photos'];

	/**
	 * write out the documentation for the tags api controller.  the page is
	 * already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<p>
				any type parameter in the tags api must be in this list:
				<?php echo implode(', ', self::$AllowedTypes); ?>
			</p>

			<h2 id=getlist>get list</h2>
			<p>
				retrieves a list of tags for a type with how many times they’ve been
				used.
			</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of tags to list.  required.</dd>
			</dl>

			<h2 id=postsetdesc>post setdesc</h2>
			<p>sets the description for a tag.  only available to admin.</p>
			<dl class=parameters>
				<dt>type</dt>
				<dd>type of the tag being updated.  required.</dd>
				<dt>id</dt>
				<dd>id of the tag being updated.  required.</dd>
				<dt>description</dt>
				<dd>new tag description to set.  required.</dd>
			</dl>
<?php
	}

	/**
	 * get tags in use.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function listAction($ajax) {
		global $db;
		if(self::IsTypeSupported($_GET['type']))
			if($tags = $db->query('select name, count from ' . $_GET['type'] . '_tags where count>1 order by lastused desc')) {
				$ajax->Data->tags = [];
				while($tag = $tags->fetch_object())
					$ajax->Data->tags[] = $tag;
			} else
				$ajax->Fail('error getting list of ' . $_GET['type'] . ' tags', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('unknown tag type for list.  supported tag types are:  ' . implode(', ', self::$AllowedTypes));
	}

	/**
	 * update the description of a tag.  admin-only.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function setdescAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['type'], $_POST['id'], $_POST['description']))
				if(self::IsTypeSupported($_POST['type'])) {
					if($db->real_query('update ' . $_POST['type'] . '_tags set description=\'' . $db->escape_string($_POST['description']) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
						if($db->affected_rows);
						else
							$ajax->Fail('tag not found or description not changed.');
					else
						$ajax->Fail('database error saving description', $db->errno . ' ' . $db->error);
				} else
					$ajax->Fail('unknown tag type for setdesc.  supported tag types are:  ' . implode(', ', self::$AllowedTypes));
			else
				$ajax->Fail('parameters type, id, and description are required.');
		else
			$ajax->Fail('only admin users can set tag descriptions.  you might need to log in again.');
	}

	/**
	 * check if the type specified is an actual tag type.
	 * @param string $type tag type to check.
	 * @return boolean whether the tag type is supported.
	 */
	private static function IsTypeSupported($type) {
		return in_array($type, self::$AllowedTypes);
	}
}
tagsApi::Respond();
