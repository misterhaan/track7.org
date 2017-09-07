<?php
/**
 * process login / registration via twitter
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
t7auth::LoginRegister(new t7authTwitter());
