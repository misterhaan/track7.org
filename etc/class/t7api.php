<?php
/**
 * base class for api controllers.  controllers should provide the
 * ShowDocumentation function as well as any ___Action functions they want to
 * provide.  requests are formed as [controller]/[method] and served by a
 * function named [method]Action in the t7api class in [controller].php.
 * @author misterhaan
 */
abstract class t7api {
	/**
	 * respond to an api request or show api documentation.
	 */
	public static function Respond() {
		if(isset($_GET['method'])) {
			$ajax = new t7ajax();
			$method = $_GET['method'];
			if(preg_match('/^[a-zA-Z0-9_]+$/', $method)) {
				$method .= 'Action';
				if(method_exists(static::class, $method))
					static::$method($ajax);
				else
					$ajax->Fail('requested method does not exist.');
			} else
				$ajax->Fail('invalid request.');
			$ajax->Send();
		} else {
			$html = new t7html();
			$name = substr($_SERVER['SCRIPT_NAME'], 5, -4);  // five for '/api/' and -4 for '.php'
			$html->Open($name . ' api');
?>
			<h1><?=$name; ?> api</h1>
<?php
			static::ShowDocumentation($html);
			$html->Close();
		}
	}

	/**
	 * make sure the url is valid and isn't already used, unless it's already used
	 * by the id specified.
	 * @param string $table name of the table that contains the url and id columns.
	 * @param string $namecol column containing the name of the item in case the url is in use.
	 * @param string $url url segment to check.
	 * @param int $id id of blog entry we're checking for, because it's okay if this entry is already using the url.
	 * @param t7ajax $ajax ajax object or reporting an error.  optional.
	 * @return boolean whether the url is valid and available.
	 */
	protected static function CheckUrl($table, $namecol, $url, $id, $ajax) {
		global $db;
		if(t7format::ValidUrlPiece($url))
			if($chk = $db->query('select ' . $namecol . ' from ' . $table . ' where url=\'' . $db->escape_string($url) . '\' and not id=\'' . +$id . '\' limit 1'))
				if($chk = $chk->fetch_object())
					$ajax->Fail('url already in use by “' . $chk->$namecol . '”');
				else
					return true;
			else
				$ajax->Fail('error checking if url is available', $db->errno . ' ' . $db->error);
		else
			$ajax->Fail('url must be at least three characters and can only contain letters, digits, periods, dashes, and underscores');
		return false;
	}

	/**
	 * Write out the documentation for the API controller.  The page is already
	 * opened with an h1 header, and will be closed after the call completes.
	 */
	protected abstract static function ShowDocumentation();
}
