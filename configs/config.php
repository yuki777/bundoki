<?php
if($_SERVER['HTTP_HOST'] == 'dev.gokibun.com'){
    require_once('configs/dev.php');
}elseif($_SERVER['HTTP_HOST'] == 'gokibun.com'){
    require_once('configs/live.php');
}elseif($_SERVER['HTTP_HOST'] == 'gokibun.nyarico.com'){
    require_once('configs/nyarico.php');
}else{
    die('unkown ENV');
}

define('LOG_FILE', 'logs/log');

// nazki
if(is_file('configs/nazki.php')){
    require_once('configs/nazki.php');
}else{
    echo "FILE NOT FOUND : configs/nazki.php<br><br>\n";
    echo "ex : <br>\n";
    echo "&lt;?php<br>\n";
    echo 'define(\'NAZKI_API_URL\', \'http://210.152.149.14/api/GetSenseFull.php?key=REPLACE_ME\');';
    exit;
}

// twitter
if(is_file('configs/twitter.php')){
    require_once('configs/twitter.php');
    define('HASH_TAG', '#gokibun');
}else{
    echo "FILE NOT FOUND : configs/twitter.php<br><br>\n";
    echo "ex : <br>\n";
    echo "&lt;?php<br>\n";
    echo 'define(\'CONSUMER_KEY\', \'REPLACE_ME\');';
    echo "<br>\n";
    echo 'define(\'CONSUMER_SECRET\', \'REPLACE_ME\');';
    exit;
}
