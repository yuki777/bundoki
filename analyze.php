<?php
require_once('configs/config.php');
require_once('libs/lib.php');

// screen_name$B$,(BREQUEST$B$K$J$$$J$i(BTOP$B$X(B
if(! $_REQUEST['screen_name']){
    echo __FILE__.__LINE__; exit;
    header('Location: ' . SITE_URL);
    exit;
}

// POST$B$J$i(BGET$B$X(B
if($_POST['screen_name']){
    $date = $_GET['date'];
    if(! $datetime) $datetime = date('YmdHis');
    echo __FILE__.__LINE__; exit;
    header('Location: ' . SITE_URL . '/analyze/' . $_REQUEST['screen_name'] . '/' . $datetime . '/');
    exit;
}

// GET$B$J$i(BHTML$BI=<((B
if($_GET['screen_name']){
    $screen_name = $_GET['screen_name'];
    $datetime = date('YmdHis');
    echo __FILE__.__LINE__; exit;
    include(get_template('html/analyze.html'));
}

