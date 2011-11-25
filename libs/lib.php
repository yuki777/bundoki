<?php

function conn_cache(){
    return memcache_connect('localhost', 11211);
}

function set_cache($k, $v){
    $k = md5($k);
    $expire = 86400;
    return memcache_set(conn_cache(), ENV . $k, serialize($v), 0, $expire);
}

function get_cache($k){
    $k = md5($k);
    return unserialize(memcache_get(conn_cache(), ENV . $k));
}

function l($params = null){
    if(! is_writable(LOG_FILE)) return false;
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

function get_status_list($screen_name, $datetime){
    $cache = get_cache('status_list.' . $datetime . '.' . $screen_name);
    if($cache){
        l('cache hit. get_user_status_list() return cache.');
        return $cache;
    }

    // get statuses, datetime, ,images,,, etc
    $timeline = get_user_timeline_unofficial($screen_name);
    // get only statuses
    $status_list = get_user_status_list_unofficial($timeline);
    l($status_list);

    if($status_list){
        l('cache NOT hit. get_user_status_list() connect twitter unofficial API.');
        set_cache('status_list.' . $datetime . '.' . $screen_name, $status_list);
    }
    return $status_list;
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

function get_user_status_list_unofficial($timeline){
    $list = array();
    $i = 0;
    foreach($timeline as $status){
        $text = $status['text'];
        $text = str_replace("\n", " ", $text);
        $text = str_replace("\r", " ", $text);
        $text = str_replace("\r\n", " ", $text);
        $text = trim($text);
        $key  = md5($text);
        $list[$i]['key'] = $key;
        $list[$i]['text'] = $text;
        $i++;
    }
    return $list;
}

function get_emotion($status){
    if(! $status) return;

    $cache = get_cache('status.' . $status);
    if($cache){
        //l("cache hit. get_emotion() return cache.");
        return $cache;
    }

    $url = NAZKI_API_URL . "&text=" . urlencode($status);
    $contents = wget($url);
    $emotion = analyze_nazki($contents, $status);

    l("cache NOT hit. get_emotion() connect NAZKI API.");
    set_cache('status.' . $status, $emotion);
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

    // ポジかネガか不明か判定
    $list['emotion_type'] = get_emotion_type($positive_count, $negative_count);

    $list['positive'] = $positive_count;
    $list['negative'] = $negative_count;
    $list['unknown'] = $unknown_count;
    $list['text'] = $status;
    $list['key'] = md5($status);

    //l($list);
    return $list;
}

function get_emotion_point($emotion_list){
    $list['total_positive'] = 0;
    $list['total_negative'] = 0;
    foreach($emotion_list as $emotion){
        $total_positive = $emotion['positive'] + $total_positive;
        $total_negative = $emotion['negative'] + $total_negative;
        $total_unknown = $emotion['unknown'] + $total_unknown;
    }
    $list['total_text'] = count($emotion_list);
    $list['total_positive'] = $total_positive;
    $list['total_negative'] = $total_negative;
    $list['total_unknown'] = $total_unknown;
    $total_emotion = $total_positive + $total_negative + $total_unknown;
    $list['total_emotion'] = $total_emotion;

    return $list;
}

function get_emotion_type($p, $n)
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


function wget($url){
    if(! $url) return false;
    usleep(500);
    $contents = file_get_contents($url);
    return $contents;
}

/** 
 * http://techblog.yahoo.co.jp/cat209/api1_curl_multi/
 * 複数URLのコンテンツ、及び通信ステータスを一括取得する。 
 * サンプル: 
 *   $urls = array( "http://〜", "http://〜", "http://〜" ); 
 *   $results = getMultiContents($urls); 
 *   print_r($results); 
 */  
function wget_multi( $url_list ) {  
    if(! $url_list) return false;
    // マルチハンドルの用意  
    $mh = curl_multi_init();  
  
    // URLをキーとして、複数のCurlハンドルを入れて保持する配列  
    $ch_list = array();  
  
    // Curlハンドルの用意と、マルチハンドルへの登録  
    foreach( $url_list as $url ) {  
        $ch_list[$url] = curl_init($url);  
        curl_setopt($ch_list[$url], CURLOPT_RETURNTRANSFER, TRUE);  
        curl_setopt($ch_list[$url], CURLOPT_TIMEOUT, 10);  // cURL 関数の実行にかけられる時間の最大値。
        curl_setopt($ch_list[$url], CURLOPT_CONNECTTIMEOUT, 0);  // 接続の試行を待ち続ける秒数。0 は永遠に待ち続けることを意味します。
        
        curl_multi_add_handle($mh, $ch_list[$url]);  
    }  
  
    // 一括で通信実行、全て終わるのを待つ  
    $running = null;  
    do { curl_multi_exec($mh, $running); } while ( $running );  
  
    // 実行結果の取得  
    foreach( $url_list as $url ) {  
        // ステータスとコンテンツ内容の取得  
        $results[$url] = curl_getinfo($ch_list[$url]);  
        $results[$url]["content"] = curl_multi_getcontent($ch_list[$url]);  
  
        // Curlハンドルの後始末  
        curl_multi_remove_handle($mh, $ch_list[$url]);  
        curl_close($ch_list[$url]);  
    }  
  
    // マルチハンドルの後始末  
    curl_multi_close($mh);  
  
    // 結果返却  
    return $results;  
}  

function make_user_timeline_unofficial($status_list){
    $i = 0;
    foreach($status_list as $status){
        //$timeline[] = $status->text;
        $timeline[$i]['key'] = md5($status->text);
        $timeline[$i]['text'] = $status->text;
        $i++;
    }
    return $timeline;
}

function get_template($file){

    if(is_smartphone()){
        $pathinfo = pathinfo($file);
        $smartphone_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.sp.' . $pathinfo['extension'];
        if(is_readable($smartphone_file)){
            $file = $smartphone_file;
        }
    }
    return($file);
}

function is_smartphone(){
    $ua_list = array('iPhone', 'iPod', 'Android', 'dream', 'CUPCAKE', 'blackberry', 'webOS', 'incognito', 'webmate');
    foreach($ua_list as $ua){
        $pattern = '/' . $ua . '/i';
        if(preg_match($pattern, $_SERVER['HTTP_USER_AGENT'])){
            if(preg_match('/Android/i', $_SERVER['HTTP_USER_AGENT'])){
                if(preg_match('/Mobile/i', $_SERVER['HTTP_USER_AGENT'])){
                    return true;
                }
            }else{
                return true;
            }
        }
    }
    return false;
}
