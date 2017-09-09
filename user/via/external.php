<?php
/**
 * process login / registration data from an external authenticator.
 * $_GET['source'] comes from the request as in /user/via/[source].php
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
if($auth = t7auth::MakeExternalAuth($_GET['source']))
	t7auth::LoginRegister($auth);
else
	include_once '../../404.php';
