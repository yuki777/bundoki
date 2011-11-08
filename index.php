<?php
//require_once('configs/config.php');
//require_once('libs/lib.php');
//include('html/index.html');

require_once('configs/config.php');
require_once('libs/lib.php');
require_once('twitteroauth/twitteroauth.php');

session_start();
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    $is_login    = false;
    $user_id     = false;
    $screen_name = false;
}else{
    $is_login    = true;
    $user_id     = $_SESSION['access_token']['user_id'];
    $screen_name = $_SESSION['access_token']['screen_name'];
}

include('html/index.html');
