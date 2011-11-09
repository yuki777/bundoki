<?php
require_once('configs/config.php');
require_once('libs/lib.php');
require_once('twitteroauth/twitteroauth.php');

//// unofficial
//$timeline     = get_user_timeline_unofficial($screen_name);
//$status_list  = get_user_status_list_unofficial($timeline);

session_start();
if(! is_login()){
    l("is NOT login. exit");
    exit;
}

$user_id     = $_SESSION['access_token']['user_id'];
$screen_name = $_SESSION['access_token']['screen_name'];
$access_token = $_SESSION['access_token'];
$connection   = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
$account      = get_user($connection);
$screen_name  = $account->screen_name;
//l("[" . $screen_name . "] is logged in");


if($_GET['mode'] == 'get_user_status_list'){
    $timeline     = get_user_timeline($connection, $account);
    $status_list  = get_user_status_list($timeline);
    //l($status_list);
    echo json_encode($status_list);
    exit;
}

if($_GET['mode'] == 'get_emotion_point'){
    $timeline     = get_user_timeline($connection, $account);
    $status_list  = get_user_status_list($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    //l($emotion_list);
    //l($point);
    echo json_encode($point);
    exit;
}

if($_GET['mode'] == 'get_message'){
    $timeline     = get_user_timeline($connection, $account);
    $status_list  = get_user_status_list($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    $message      = get_message($account, $point);
    //l($message);
    echo json_encode($message);
    exit;
}

if($_GET['mode'] == 'get_tweet_link'){
    $timeline     = get_user_timeline($connection, $account);
    $status_list  = get_user_status_list($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    $message      = get_message($account, $point);
    $link = get_tweet_link($message);
    //l($link);
    echo json_encode($link);
    exit;
}

