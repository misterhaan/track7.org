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
		if(isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'], 0, 1) == '/') {
			$ajax = new t7ajax();
			$method = substr($_SERVER['PATH_INFO'], 1);
			if(false === strpos($method, '/')) {
				$method .= 'Action';
				if(method_exists(static::class, $method))
					static::$method($ajax);
				else
					$ajax->Fail('Requested method does not exist.');
			} else
				$ajax->Fail('Invalid request.');
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
	 * Write out the documentation for the API controller.  The page is already
	 * opened with an h1 header, and will be closed after the call completes.
	 */
	protected abstract static function ShowDocumentation();
}
