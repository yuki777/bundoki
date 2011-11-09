<?php
require_once('configs/config.php');
require_once('libs/lib.php');

if(! $_POST['screen_name'] && ! $_GET['screen_name']){
    header('Location: ' . SITE_URL);
    exit;
}

if($_POST['screen_name']){
    l("is POST. redirect /analyze/" . $_REQUEST['screen_name'] . '/');
    header('Location: ' . SITE_URL . '/analyze/' . $_REQUEST['screen_name'] . '/');
    exit;
}

if($_GET['screen_name']){
    $screen_name = $_GET['screen_name'];
    output('html/analyze.html');
}

