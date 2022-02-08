<?php
set_include_path(dirname(__FILE__));
session_start();

ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// set the timezone to system tz since php 5.3 refuses to use system tz
date_default_timezone_set(@date('e'));

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/.t7keys.php';

$db = new mysqli(t7keysDB::HOST, t7keysDB::USER, t7keysDB::PASS, t7keysDB::NAME);
$db->real_query('set names \'utf8mb4\'');
$db->set_charset('utf8mb4');

spl_autoload_register(function ($class) {
	switch($class) {
		case 't7ajax':
			require_once 't7ajax.php';
			break;
		case 't7api':
			require_once 't7api.php';
			break;
		case 't7auth':
		case 't7authGoogle':
		case 't7authTwitter':
		case 't7authFacebook':
		case 't7authSteam':
		case 't7authTrack7':
		case 't7authGithub':
			require_once 't7auth.php';
			break;
		case 't7contrib':
			require_once 't7contrib.php';
			break;
		case 't7feed':
			require_once 't7feed.php';
			break;
		case 't7file':
			require_once 't7file.php';
			break;
		case 't7format':
			require_once 't7format.php';
			break;
		case 'Parsedown':
			require_once 'Parsedown.php';
			break;
		case 't7html':
			require_once 't7html.php';
			break;
		case 't7send':
			require_once 't7send.php';
			break;
		case 't7user':
			require_once 't7user.php';
			break;
	}
});

$user = new t7user();
