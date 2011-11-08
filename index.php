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
    $is_login    = false;
    $user_id     = false;
    $screen_name = false;
}

include('html/index.html');
