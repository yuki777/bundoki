<?php
require_once('configs/config.php');
require_once('libs/lib.php');

// screen_nameがREQUESTにないならTOPへ
if(! $_REQUEST['screen_name']){
    header('Location: ' . SITE_URL);
    exit;
}

// POSTならGETへ
if($_POST['screen_name']){
    if(! $datetime) $datetime = date('YmdHis');

    // nyarico.comはmod_rewriteが効かないっぽいのでquery stringでリダイレクト。
    if($_SERVER['HTTP_HOST'] == 'gokibun.nyarico.com'
    //|| $_SERVER['HTTP_HOST'] == 'dev.gokibun.com'
    ){
        header('Location: ' . SITE_URL . '/analyze.php?screen_name=' . $_REQUEST['screen_name'] . '&datetime=' . $datetime);
        exit;
    }else{
        header('Location: ' . SITE_URL . '/analyze/' . $_REQUEST['screen_name'] . '/' . $datetime . '/');
        exit;
    }
}

// GETならHTML表示
if($_GET['screen_name']){
    $screen_name = $_GET['screen_name'];
    $datetime = date('YmdHis');
    include(get_template('html/analyze.html'));
}

die('UNKNOWN REQUEST ERROR');
