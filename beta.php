<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

header("Content-Type: text/html; charset=utf-8");

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
//$content = $connection->get('account/verify_credentials');
//$account = $connection->get('account/verify_credentials');
$account = get_user($connection);

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham')));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992)));
//$connection->post('friendships/destroy', array('id' => 9436992)));

//$content = $connection->get('statuses/user_timeline', array('screen_name' => $account->screen_name));
$timeline = get_user_timeline($connection, $account);
$status_list = get_user_status_list($timeline);
$emotion_list = get_emotion_by_list($status_list);
//v($emotion_list);
$point = get_emotion_point($emotion_list);
//v($point);
//$json = get_json($emotion_list);
//echo $json;


/* Include HTML to display on the page */
//include('html.inc');
include('html/beta.html');


function v($params = null){
    if(DEBUG){
        var_dump($params);
        echo "<br>\n";
    }
    error_log(date('Y-m-d H:i:s')." ".__FILE__." ".__METHOD__." ".__LINE__." ".var_export($params,true)."\n", 3, '/var/www/bundoki.coz.jp/logs/log');
    return;
}

function get_user($connection){
    $account = $connection->get('account/verify_credentials');
    return $account;
}

function get_user_timeline($connection, $account){
    $cache = get_cache($account->screen_name);
    if($cache){
        //v("cache hit.");
        //v($cache);
        return $cache;
    }
    $content = $connection->get('statuses/user_timeline', array('screen_name' => $account->screen_name));
    if($content){
        //v("set cache.");
        //v($content);
        set_cache($account->screen_name, $content);
    }
    return $content;
}

function get_user_status_list($timeline){
    $list = array();
    foreach($timeline as $status){
        //v($status->text);
        $text = $status->text;
        $text = str_replace("\n", " ", $text);
        $text = str_replace("\r", " ", $text);
        $text = str_replace("\r\n", " ", $text);
        $list[] = trim($text);

        // XXX:debug
        //if(count($list) == 3) break;
    }
    return $list;
}
function get_emotion_by_list($status_list){
    foreach($status_list as $status){
        //$list[] = get_emotion_alpha($status);
        $list[] = get_emotion_beta($status);
    }
    //v($list);
    //v($_list);
    return $list;
}


// 辞書から感情抽出するver
function get_emotion_beta($status){
    // positive
    $positive = get_positive($status);
    $positive_count = $positive['count'];
    $positive_point = $positive['point'];
    v(" ++++++++++++++++++++++++++++ ");
    v(" + text  : ".$status);
    v(" + count : ".$positive_count);
    v(" + point : ".$positive_point);
    v(" + word  : ".$positive['word']);
    v(" ++++++++++++++++++++++++++++ ");

    // negative
    $negative = get_negative($status);
    $negative_count = $negative['count'];
    $negative_point = $negative['point'];
    v(" ---------------------------- ");
    v(" - text  : ".$status);
    v(" - count : ".$negative_count);
    v(" - point : ".$negative_point);
    v(" - word  : ".$negative['word']);
    v(" ---------------------------- ");

    // unknown
    if(! $positive_count && ! $negative_count){
        $unknown_count = 1;
    }
    v(" ???????????????????????????? ");
    v(" ? text  : ".$status);
    v(" ? count : ".$unknown_count);
    v(" ???????????????????????????? ");

    // which
    $emotion['positive_or_negative'] = 'unknown';
    if($positive_count > $negative_count){
        $emotion['positive_or_negative'] = 'positive';
    }elseif($positive_count < $negative_count){
        $emotion['positive_or_negative'] = 'negative';
    }

    $emotion['text'] = $status;
    $emotion['positive_count'] = $positive_count;
    $emotion['negative_count'] = $negative_count;
    $emotion['unknown_count'] = $unknown_count;
    return $emotion;
}

// 感情抽出テキトーver
function get_emotion_alpha($status){
    // positive
    $pattern = "/買|良|イイ|いい|楽|ラク|vim|テクニック|元気|やった|(！|\!)/";
    if(preg_match_all($pattern, $status, $matches)){
        $positive_count = count($matches[0]);
    }

    // negative
    $pattern = "/ない|なかった|むかつく|ムカツク|怒|なにぃ|いらん|いらない|死んだ|死|なんて|ダメ|駄目|(？|\?)/";
    if(preg_match_all($pattern, $status, $matches)){
        $negative_count = count($matches[0]);
    }

    // unknown
    if(! $positive_count && ! $negative_count){
        $unknown_count = 1;
    }

    // which
    $emotion['positive_or_negative'] = 'unknown';
    if($positive_count > $negative_count){
        $emotion['positive_or_negative'] = 'positive';
    }elseif($positive_count < $negative_count){
        $emotion['positive_or_negative'] = 'negative';
    }

    $emotion['text'] = $status;
    $emotion['positive_count'] = $positive_count;
    $emotion['negative_count'] = $negative_count;
    $emotion['unknown_count'] = $unknown_count;
    return $emotion;
}


function get_emotion_point($emotion_list){
    $list['total_positive'] = 0;
    $list['total_negative'] = 0;
    foreach($emotion_list as $emotion){
        $total_positive = $emotion['positive_count'] + $total_positive;
        $total_negative = $emotion['negative_count'] + $total_negative;
        $total_unknown = $emotion['unknown_count'] + $total_unknown;
    }
    $list['total_text'] = count($emotion_list);
    $list['total_positive'] = $total_positive;
    $list['total_negative'] = $total_negative;
    $list['total_unknown'] = $total_unknown;
    $total_emotion = $total_positive + $total_negative + $total_unknown;
    $list['total_emotion'] = $total_emotion;
    $list['percent_positive'] = round(($total_positive / $total_emotion) * 100);
    $list['percent_negative'] = round(($total_negative / $total_emotion) * 100);
    $list['percent_unknown'] = round(($total_unknown  / $total_emotion) * 100);
    return $list;
}

function get_json($list){
    return json_encode($list);
}

function conn_cache(){
    return memcache_connect('localhost', 11211);
}

function set_cache($k, $v){
    $expire = 60;          // 通常      ：60秒キャッシュ保存
    if(DEBUG) $expire = 3; // デバッグ中： 3秒キャッシュ保存
    return memcache_set(conn_cache(), $k, serialize($v), 0, $expire);
}

function get_cache($k){
    if(DEBUG) return; // デバッグ中はキャッシュヒットさせない
    return unserialize(memcache_get(conn_cache(), $k));
}

function get_positive_dict(){
    $cache = get_cache('positive_dict');
    if($cache) return $cache;
    
    $dict = file_get_contents(POSITIVE_DICT);
    set_cache('positive_dict', $dict);
    return $dict;
}
function get_negative_dict(){
    $cache = get_cache('negative_dict');
    if($cache) return $cache;
    
    $dict = file_get_contents(NEGATIVE_DICT);
    set_cache('negative_dict', $dict);
    return $dict;
}

function get_positive_list($dict){
    $dict_list = explode("\n", $dict);
    $i = 0;
    foreach($dict_list as $line){
        $split = explode(":", $line);
        if($split[0] == '') continue;
        if($split[1] == '') continue;
        $list[$i]['word'] = $split[0];
        $list[$i]['point'] = $split[1];
        $i++;
    }
    return $list;
}
function get_negative_list($dict){
    $dict_list = explode("\n", $dict);
    $i = 0;
    foreach($dict_list as $line){
        $split = explode(":", $line);
        if($split[0] == '') continue;
        if($split[1] == '') continue;
        $list[$i]['word'] = $split[0];
        $list[$i]['point'] = $split[1];
        $i++;
    }
    return $list;
}



function get_positive($text){
    $dict = get_positive_dict();
    $list = get_positive_list($dict);
    foreach($list as $positive){
        if(FALSE !== strpos($text, $positive['word'])){
            if(in_array($positive['word'], $hit_list)) continue;
            $hit_list[] = $positive['word'];

            $word = $word . "," . $positive['word'];
            $point = $point + $positive['point'];
            $count++;
        }
    }
    $res['word'] = $word;
    $res['point'] = $point;
    $res['count'] = $count;
    return $res;
}


function get_negative($text){
    $dict = get_negative_dict();
    $list = get_negative_list($dict);
    foreach($list as $negative){
        if(FALSE !== strpos($text, $negative['word'])){
            if(in_array($negative['word'], $hit_list)) continue;
            $hit_list[] = $negative['word'];

            $word = $word . "," . $negative['word'];
            $point = $point + $negative['point'];
            $count++;
        }
    }
    $res['word'] = $word;
    $res['point'] = $point;
    $res['count'] = $count;
    return $res;
}
