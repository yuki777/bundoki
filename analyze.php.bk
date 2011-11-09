<?php
require_once('configs/config.php');
require_once('libs/lib.php');
require_once('twitteroauth/twitteroauth.php');

session_start();
if(is_login()){
    $is_login    = true;
    $user_id     = $_SESSION['access_token']['user_id'];
    $screen_name = $_SESSION['access_token']['screen_name'];
}else{
    header('Location: ./clearsessions.php');
}

$access_token = $_SESSION['access_token'];
$connection   = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
$account      = get_user($connection);
$screen_name  = $account->screen_name;

include('html/analyze.html');

