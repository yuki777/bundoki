<?php
require_once('configs/config.php');
require_once('libs/lib.php');

// screen_name$B$,(BREQUEST$B$K$J$$$J$i(BTOP$B$X(B
if(! $_REQUEST['screen_name']){
    header('Location: ' . SITE_URL);
    exit;
}

// POST$B$J$i(BGET$B$X(B
if($_POST['screen_name']){
    $date = $_GET['date'];
    if(! $datetime) $datetime = date('YmdHis');
    if($_SERVER['HTTP_HOST'] == 'dev.gokibun.com'
    || $_SERVER['HTTP_HOST'] == 'gokibun.nyarico.com'){
        header('Location: ' . SITE_URL . '/analyze.php?screen_name=' . $_REQUEST['screen_name'] . '&datetime=' . $datetime);
        exit;
    }else{
        header('Location: ' . SITE_URL . '/analyze/' . $_REQUEST['screen_name'] . '/' . $datetime . '/');
        exit;
    }
}

// GET$B$J$i(BHTML$BI=<((B
if($_GET['screen_name']){
    $screen_name = $_GET['screen_name'];
    $datetime = date('YmdHis');
    include(get_template('html/analyze.html'));
}

