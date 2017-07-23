<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');

$canadianServer = '...';
$usaServer = '...';

$bu_url = $_REQUEST['bu_url'];
$key_url = $_REQUEST['k_url'];
$token = $_REQUEST['token'];
$video_url = $_REQUEST['video_url'];

preg_match('~(\d+)~', $video_url, $c);

$video_id = $c[0];
        
$o = array('error' => 'There was an error.');
    
if($video_url){
    $storedCache = getCache($video_id);
    $cache = ($storedCache != '""') ? $storedCache : false;

    if($bu_url){
        if($cache && array_key_exists('s', $cache) && $cache['s']){
                $o = array('c' => array(
                    's' => $cache['s'],
                    'k' => $cache['k']
                ));
        }else{
            $fields = array(
                'target_url' => $bu_url,
                'video_url' => $video_url
            );

            $r = sendRequest($canadianServer, $fields);

            if(array_key_exists('errors', $r['s'])){
                $r = sendRequest($usaServer, $fields);
            }

            //cache
            writeToCache($video_id, 's', $r['s']);
            writeToCache($video_id, 't', $r['token']);

            $o = array('s' => $r['s']);
        }
    }else{
        if($cache && array_key_exists('k', $cache) && $cache['k']){
            $o = array('c' => array(
                's' => $cache['s'],
                'k' => $cache['k']
            ));
        }else{
            $fields = array(
                'k_url' => $key_url,
                'token' => $cache['t'],
                'video_url' => $video_url
            );

            $k = sendRequest($canadianServer, $fields);

            if(!$k['k']){
                $k = sendRequest($usaServer, $fields);
            }

            //cache
            writeToCache($video_id, 'k', $k['k']);

            $o = array('k' => $k['k']);
        }
    }

    $fh = fopen('log_'.$video_id.'.txt', 'a');
    fwrite($fh, sha1($_SERVER['REMOTE_ADDR']).' requesting video.'.PHP_EOL);
    fclose($fh);
}

echo base64_encode(json_encode($o));

function sendRequest($server, $fields){
    $ch = curl_init($server);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $fields
    ));
    $r = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($r, true);
}

function getCache($video_id){
    $file = 'stream_sk_cache_'.$video_id.'.txt';
    
    if(file_exists($file)){
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        return $data;
    }
    
    return false;
}

function writeToCache($video_id, $type, $value){
    $file = 'stream_sk_cache_'.$video_id.'.txt';
    
    if(file_exists($file)){
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        $data[$type] = $value;
        $data = json_encode($data);
        
        $fh = fopen($file,'w');
        fwrite($fh, $data);
        fclose($fh);
    }else{
        $data = array($type => $value);
        $data = json_encode($data);
        
        $fh = fopen($file,'w');
        fwrite($fh, $data);
        fclose($fh);
    }
}