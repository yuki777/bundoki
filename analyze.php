<?php
session_start();
require_once('configs/config.php');
require_once('libs/lib.php');
require_once('twitteroauth/twitteroauth.php');

if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}else{
    $is_login    = true;
    $user_id     = $_SESSION['access_token']['user_id'];
    $screen_name = $_SESSION['access_token']['screen_name'];
}

$access_token = $_SESSION['access_token'];
$connection   = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
$account      = get_user($connection);
$screen_name  = $account->screen_name;
$timeline     = get_user_timeline($connection, $account);
$status_list  = get_user_status_list($timeline);
$emotion_list = get_emotion_list($status_list);
$point        = get_emotion_point($emotion_list);
$message      = get_message($account, $point);

include('html/result.html');

