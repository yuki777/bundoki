<?php

function conn_cache(){
    return memcache_connect('localhost', 11211);
}

function set_cache($k, $v){
    $expire = 600;         // 通常      ：600秒キャッシュ保存
    if(DEBUG) $expire = 3; // デバッグ中：  3秒キャッシュ保存
    return memcache_set(conn_cache(), $k, serialize($v), 0, $expire);
}

function get_cache($k){
    if(DEBUG) return; // デバッグ中はキャッシュヒットさせない
    return unserialize(memcache_get(conn_cache(), $k));
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
        return $cache;
    }
    $content = $connection->get('statuses/user_timeline', array('screen_name' => $account->screen_name));
    if($content){
        set_cache('screen.' . $account->screen_name, $content);
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

        // XXX:debug
        //if(count($list) == 3) break;
    }
    return $list;
}

function get_emotion_list($status_list){
    foreach($status_list as $status){
        $i++;
        $list[] = get_emotion($status);
        //if($i == 1) break;
        if($i == 3) break;
        if($i == 5) break;
    }
    return $list;
}

function get_emotion($status){
    if(! $status) return;

    $cache = get_cache('status.' . md5($status));
    if($cache){
        return $cache;
    }

    $url = NAZKI_API_URL . "&text=" . urlencode($status);
    $contents = file_get_contents($url);
    $emotion = analyze($contents, $status);

    set_cache('status.' . md5($status), $emotion);
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

function analyze($contents, $status){
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
                    l(var_export(" > sense : 好評, " . $text,true));
                    $positive_count++;
                }elseif($sense == '不評'){
                    l(var_export(" > sense : 不評, " . $text,true));
                    $negative_count++;
                }else{
                    l(var_export("!!! > sense : $sense, " . $text,true));
                    $unknown_count++;
                }
            }
            // 感性なし
            else{
                $unknown_count++;
                l(var_export(" > sense : NO, " . $text,true));
            }

        }
    }
    // 感情なし
    else{
        l(var_export("emotion : NO, " . $status,true));
    }

    // ポジかネガか判定
    $list['positive_or_negative'] = check_positive_or_negative($positive_count, $negative_count);

    $list['positive_count'] = $positive_count;
    $list['negative_count'] = $negative_count;
    $list['unknown_count'] = $unknown_count;
    $list['text'] = $status;
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
function get_message($account, $list){
    $screen_name = $account->screen_name;
    $site_url = SITE_URL;
    if($list['percent_unknown'] == 100){
        return $screen_name . "さんの、ゴキゲンもフキゲンも検知できませんでした " . $site_url;
    }
    if($list['percent_positive'] == 100){
        return $screen_name . "さんの、ゴキゲン100%を検知しました " . $site_url;
    }
    if($list['percent_negative'] == 100){
        return $screen_name . "さんの、フキゲン100%を検知しました " . $site_url;
    }


    $message = $screen_name . 'さんの、';
    if($list['percent_positive'] > 0){
        $message .= 'ゴキゲンを'.$list['percent_positive'].'% ';
    }
    if($list['percent_negative'] > 0){
        $message .= 'フキゲンを'.$list['percent_negative'].'% ';
    }
    if($list['percent_unknown'] > 0){
        $message .= 'よく分からないものを'.$list['percent_negative'].'% ';
    }
    if($message) $message.= '検知しました ' . $site_url;;

    return $message;
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
        l("! account error. redirect to connect.php");
        l($account);
        $url = SITE_URL . '/connect.php';
        header('Location: ' . $url); 
        exit;
    }

    if($account->error){
        l($account);
        return true;
    }

    return false;
}
