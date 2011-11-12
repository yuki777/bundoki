<?php
require_once('configs/config.php');
require_once('libs/lib.php');

$screen_name = $_GET['screen_name'];
$mode = $_GET['mode'];
l($screen_name);
l($mode);

if($mode == 'get_user_status_list'){
    $timeline     = get_user_timeline_unofficial($screen_name);
    $status_list  = get_user_status_list_unofficial($timeline);
    $data         = array('status_list' => $status_list);
    $data         = json_encode($data);
    echo $data;
    exit;
}

if($mode == 'get_data'){
    $timeline     = get_user_timeline_unofficial($screen_name);
    $status_list  = get_user_status_list_unofficial($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    $message      = get_message($screen_name, $point);
    $link         = get_tweet_link($message);
    $data         = array(
        'timeline'     => $timeline,
        'status_list'  => $status_list,
        'emotion_list' => $emotion_list,
        'point'        => $point,
        'message'      => $message,
        'link'         => $link,
    );
    $data = json_encode($data);
    echo $data;
    exit;
}
