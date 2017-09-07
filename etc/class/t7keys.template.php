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

class t7keysTwitter {
	/**
	 * consumer key (api key) from apps.twitter.com after selecting your app
	 * @var string
	 */
	const CONSUMER_KEY = '';

	/**
	 * consumer secret (api secret) from apps.twitter.com after selecting your app
	 * @var string
	 */
	const CONSUMER_SECRET = '';

	/**
	 * access token from apps.twitter.com under your access token heading
	 * @var string
	 */
	const OAUTH_TOKEN = '';

	/**
	 * access token secret from apps.twitter.com under your access token heading
	 * @var string
	 */
	const OAUTH_TOKEN_SECRET = '';
}

class t7keysFacebook {
	/**
	 * app id from facebook app basic settings
	 * @var string
	 */
	const ID = '';

	/**
	 * client secret from facebook app basic settings
	 * @var string
	 */
	const SECRET = '';
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

class t7keysTweet {
	/**
	 * consumer key (api key) from apps.twitter.com after selecting your app
	 * @var string
	 */
	const CONSUMER_KEY = '';

	/**
	 * consumer secret (api secret) from apps.twitter.com after selecting your app
	 * @var string
	 */
	const CONSUMER_SECRET = '';

	/**
	 * access token from apps.twitter.com under your access token heading
	 * @var string
	 */
	const OAUTH_TOKEN = '';

	/**
	 * access token secret from apps.twitter.com under your access token heading
	 * @var string
	 */
	const OAUTH_TOKEN_SECRET = '';
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
	 * email address of the cloudflare account
	 * @var string
	 */
	const EMAIL = '';

	/**
	 * global api key from https://www.cloudflare.com/a/profile
	 * @var string
	 */
	const GLOBAL_API_KEY = '';

	/**
	 * id returned from requesting https://api.cloudflare.com/client/v4/zones with email and global api key
	 * @var string
	 */
	const ID = '';
}
