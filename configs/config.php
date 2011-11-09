<?php
if($_SERVER['HTTP_HOST'] == 'dev.gokibun.com'){
    require_once('configs/dev.php');
}elseif($_SERVER['HTTP_HOST'] == 'gokibun.com'){
    require_once('configs/live.php');
}else{
    die('unkown ENV');
}

// アプリケーションログファイル
define('LOG_FILE', 'logs/log');

// 感情解析API
require_once('configs/nazki.php');
//define('NAZKI_API_URL', 'http://210.152.149.14/api/GetSenseFull.php?key=REPLACE_ME');

// twitter
require_once('configs/twitter.php');
//define('CONSUMER_KEY', 'REPLACE_ME');
//define('CONSUMER_SECRET', 'REPLACE_ME');
define('HASH_TAG', '#gokibun');
