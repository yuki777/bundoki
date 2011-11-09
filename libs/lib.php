<?php

function conn_cache(){
    return memcache_connect('localhost', 11211);
}

function set_cache($k, $v){
    $expire = 3600;
    return memcache_set(conn_cache(), ENV . $k, serialize($v), 0, $expire);
}

function get_cache($k){
    return unserialize(memcache_get(conn_cache(), ENV . $k));
}

function l($params = null){
    $debug = debug_backtrace();
    $file = $debug[0]['file'];
    $line = $debug[0]['line'];
    $function = $debug[1]['function'];
    $date = date('Y-m-d H:i:s');
    $body = var_export($params, true);
    $body = "$date $file $function $line $body \n";
    return error_log($body, 3, LOG_FILE);
}

function v($params = null){
    if(DEBUG){
        var_dump($params);
        echo "<br>\n";
    }
    return l($params);
}

function get_user($connection){
    $account = $connection->get('account/verify_credentials');
    return $account;
}

function get_user_timeline($connection, $account){
    $cache = get_cache('screen.' . $account->screen_name);
    if($cache){
        //l('cache hit. get_user_timeline() return cache.');
        return $cache;
    }
    $content = $connection->get('statuses/user_timeline', array('screen_name' => $account->screen_name));
    if($content){
        l('cache NOT hit. get_user_timeline() connect twitter API.');
        set_cache('screen.' . $account->screen_name, $content);
    }
    return $content;
}

/**
 * OAuth使わずにAPIURL直叩き
 */
function get_user_timeline_unofficial($screen_name){
    $cache = get_cache('unofficial.screen.' . $screen_name);
    if($cache){
        l('cache hit. get_user_timeline_unofficial() return cache.');
        return $cache;
    }
    $url = "https://api.twitter.com/1/statuses/user_timeline.json?include_entities=true&include_rts=true&count=20&screen_name=" . $screen_name;
    $content = wget($url);
    $content = json_decode($content);
    $timeline = make_user_timeline_unofficial($content);
    $content = get_user_status_list_unofficial($timeline);
    if($content){
        l('cache NOT hit. get_user_timeline_unofficial() connect twitter unofficial API.');
        set_cache('unofficial.screen.' . $screen_name, $content);
    }
    return $content;
}

function get_user_status_list($timeline){
    $list = array();
    foreach($timeline as $status){
        $text = $status->text;
        $text = str_replace("\n", " ", $text);
        $text = str_replace("\r", " ", $text);
        $text = str_replace("\r\n", " ", $text);
        $list[] = trim($text);
    }
    return $list;
}
function get_user_status_list_unofficial($timeline){
    $list = array();
    foreach($timeline as $status){
        $text = $status;
        $text = str_replace("\n", " ", $text);
        $text = str_replace("\r", " ", $text);
        $text = str_replace("\r\n", " ", $text);
        $list[] = trim($text);
    }
    return $list;
}


function get_emotion_list($status_list){
    foreach($status_list as $status){
        $list[] = get_emotion($status);
    }
    return $list;
}

function get_emotion($status){
    if(! $status) return;

    $cache = get_cache('status.' . md5($status));
    if($cache){
        //l("cache hit. get_emotion() return cache.");
        return $cache;
    }

    $url = NAZKI_API_URL . "&text=" . urlencode($status);
    $contents = file_get_contents($url);
    $emotion = analyze_nazki($contents, $status);

    l("cache NOT hit. get_emotion() connect NAZKI API.");
    set_cache('status.' . md5($status), $emotion);
    return $emotion;
}

function analyze_nazki($contents, $status){
    // 感情あり
    $positive_count = 0;
    $negative_count = 0;
    $unknown_count  = 0;
    if(strpos($contents, "<ResultSet>")){
        $xml = simplexml_load_string($contents);
        foreach($xml as $node){
            $text = $node->SentenceDisp;
            $sense = $node->AnalysisGroup->AnalysisData->Sense;
            // 感性あり
            $sense = (array)$sense;
            $sense = $sense[0];
            if($sense){
                if($sense == '好評'){
                    //l(var_export(" > sense : 好評, " . $text,true));
                    $positive_count++;
                }elseif($sense == '不評'){
                    //l(var_export(" > sense : 不評, " . $text,true));
                    $negative_count++;
                }else{
                    l(var_export("!!! > sense : $sense, " . $text,true));
                    $unknown_count++;
                }
            }
            // 感性なし(感情はあるけど、好評|不評はなかったということ???)
            else{
                $unknown_count++;
                l(var_export(" > sense : NO, " . $text,true));
            }
        }
    }
    // 感情なし
    else{
        $unknown_count++;
        //l(var_export("emotion : NO, " . $status,true));
    }

    // ポジかネガか判定
    $list['positive_or_negative'] = check_positive_or_negative($positive_count, $negative_count);

    $list['positive_count'] = $positive_count;
    $list['negative_count'] = $negative_count;
    $list['unknown_count'] = $unknown_count;
    $list['text'] = $status;

    //l($list);
    return $list;
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

    return $list;
}

function check_positive_or_negative($p, $n)
{
    if($p == 0 && $n == 0){
        return 'unknown';
    }elseif($p >= $n){
        return 'positive';
    }elseif($p < $n){
        return 'negative';
    }else{
        return 'unknown';
    }

    return 'unknown';
}
function get_message($screen_name, $list){
    $footer   = HASH_TAG . ' ' . SITE_URL;
    $message  = SITE_NAME . 'が @' . $screen_name . ' さんの、';
    $message .= 'ゴキゲンを' . $list['total_positive'] . '°、';
    $message .= 'フキゲンを' . $list['total_negative'] . '°、';
    $message .= 'よくわからないものを' . $list['total_unknown'] . '°、';
    $message .= '検知しました。' . $footer;
    return $message;
}

function get_tweet_link($message){
    if(! $message) return false;
    $link = "https://twitter.com/intent/tweet?text=+" . urlencode($message) . "+";
    return $link;
}


function is_account_error($account)
{
    if(isset($account->error) === false){
        return false;
    }

    if(! $account){
        l($account);
        return true;
    }

    if($account->error == 'Invalid application'
    || $account->error == 'Could not authenticate you.'
    || $account->error == 'Could not authenticate with OAuth.'){
        l("! account error. redirect to index.php");
        l($account);
        $url = SITE_URL . '/index.php';
        header('Location: ' . $url); 
        exit;
    }

    if($account->error){
        l($account);
        return true;
    }

    return false;
}

function is_login()
{
    if (empty($_SESSION['access_token'])
     || empty($_SESSION['access_token']['oauth_token'])
     || empty($_SESSION['access_token']['oauth_token_secret'])) {
        return false;
    }

    return true;
}

function wget($url){
    if(! $url) return false;
    $contents = file_get_contents($url);
    return $contents;
}

function make_user_timeline_unofficial($status_list){
    foreach($status_list as $status){
        $timeline[] = $status->text;
    }

    return $timeline;
}
