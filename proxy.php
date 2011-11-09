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
    //l($status_list);
    echo json_encode($status_list);
    exit;
}

if($mode == 'get_emotion_point'){
    $timeline     = get_user_timeline_unofficial($screen_name);
    $status_list  = get_user_status_list_unofficial($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    //l($emotion_list);
    //l($point);
    echo json_encode($point);
    exit;
}

if($mode == 'get_message'){
    $timeline     = get_user_timeline_unofficial($screen_name);
    $status_list  = get_user_status_list_unofficial($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    $message      = get_message($screen_name, $point);
    $message = json_encode(array('message' => $message));
    //l($message);
    echo $message;
    exit;
}

if($mode == 'get_tweet_link'){
    $timeline     = get_user_timeline_unofficial($screen_name);
    $status_list  = get_user_status_list_unofficial($timeline);
    $emotion_list = get_emotion_list($status_list);
    $point        = get_emotion_point($emotion_list);
    $message      = get_message($screen_name, $point);
    $link = get_tweet_link($message);
    $link = json_encode(array('link' => $link));
    //l($link);
    echo $link;
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
    //l($data);
    echo $data;
    exit;
}
