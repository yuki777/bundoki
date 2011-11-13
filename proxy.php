<?php
require_once('configs/config.php');
require_once('libs/lib.php');

$mode = $_GET['mode'];

if($mode == 'get_status_list'){
    $status_list = get_status_list($_GET['screen_name'], $_GET['datetime']);

    $status = 500;
    if($status_list) $status = 200;
    $header = array('datetime' => date('Y:m:d H:i:s'), 'count' => count($status_list));
    $data   = array('status' => $status, 'header' => $header, 'body' => $status_list);
    $data   = json_encode($data);

    echo $data;
    exit;
}

if($mode == 'get_emotion'){
    $emotion = get_emotion(urldecode($_POST['status']));

    $status = 500;
    if($emotion) $status = 200;
    $header = array('datetime' => date('Y:m:d H:i:s'));
    $data   = array('status' => $status, 'header' => $header, 'body' => $emotion);
    $data   = json_encode($data);

    echo $data;
    exit;
}

