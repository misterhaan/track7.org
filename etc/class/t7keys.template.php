<?php

/**
 * access keys template for t7 classes.  copy one directory level up from
 * document root, name .t7keys.php, and fill in with access values.  this
 * obviously needs to be left blank on github to avoid sharing secrets.
 */

class t7keysDB {
	/**
	 * hostname for database (often this is localhost)
	 * @var string
	 */
	const HOST = '';

	/**
	 * name of database
	 * @var string
	 */
	const NAME = '';

	/**
	 * username with access to the database
	 * @var string
	 */
	const USER = '';

	/**
	 * password for user with access to the database
	 * @var string
	 */
	const PASS = '';
}

class t7keysGoogle {
	/**
	 * client id from google developer console
	 * @var string
	 */
	const ID = '';

	/**
	 * client secret from google developer console
	 * @var string
	 */
	const SECRET = '';
}

/**
 * keys for the Twitter API.  set up at https://developer.twitter.com/en/portal/projects-and-apps
 */
class KeysTwitter {
	/**
	 * consumer key (api key) from the authentication tokens section of the keys and tokens tab of your app.  hint can show the last 6 characters to verify.
	 * @var string
	 */
	const ConsumerKey = '';

	/**
	 * consumer secret (api secret) from the authentication tokens section of the keys and tokens tab of your app.  it won’t tell you anything about this if you already set it up, but you can regenerate if needed.
	 * @var string
	 */
	const ConsumerSecret = '';

	/**
	 * client id from the authentication tokens section of the keys and tokens tab of your app.  the entire value stays visible.
	 * @var string
	 */
	const ClientID = '';

	/**
	 * client secret from the authentication tokens section of the keys and tokens tab of your app.  hint can show the last 6 characters to verify.
	 * @var string
	 */
	const ClientSecret = '';
}

class t7keysGithub {
	/**
	 * client id from https://github.com/settings/developers after selecting your app
	 * @var string
	 */
	const CLIENT_ID = '';

	/**
	 * client secret from https://github.com/settings/developers after selecting your app
	 * @var string
	 */
	const CLIENT_SECRET = '';
}

class t7keysDeviantart {
	/**
	 * client_id for your application, found at https://www.deviantart.com/developers/apps
	 * @var string
	 */
	const CLIENT_ID = '';

	/**
	 * client_secret for your application, found at https://www.deviantart.com/developers/apps
	 * @var string
	 */
	const CLIENT_SECRET = '';
}

class KeysTwitch {
	/**
	 * client id from https://dev.twitch.tv/console/apps after selecting your app
	 * @var string
	 */
	const ClientID = '';

	/**
	 * client secret from https://dev.twitch.tv/console/apps after selecting your app
	 * @var string
	 */
	const ClientSecret = '';
}

class t7keysBitly {
	/**
	 * bitly account login name
	 * @var string
	 */
	const LOGIN = '';

	/**
	 * bitly account api key
	 * @var string
	 */
	const KEY = '';
}

class t7keysCloudflare {
	/**
	 * zone id shown in the bottom right "API" section of the website page in cloudflare
	 * @var string
	 */
	const ID = '';

	/**
	 * api token created at https://dash.cloudflare.com/profile/api-tokens with Zone.Cache Purge access
	 */
	const TOKEN = '';
}
