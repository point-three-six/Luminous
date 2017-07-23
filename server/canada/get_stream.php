<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

$url = $_REQUEST['target_url'];
$video_url = $_REQUEST['video_url'];
$key_url = $_REQUEST['k_url'];
$token_provided = $_REQUEST['token'];

$base_token = 'eyJncmFudF90eXBlIjoidXJuOm1sYmFtOnBhcmFtczpvYXV0aDpncmFudF90eXBlOnRva2VuIiwiY29udGV4dCI6ImV5SmhiR2NpT2lKSVV6STFOaUo5LmV5SnBjR2xrSWpvaU5HRTRZMll4TmpRdE9UVmlPUzAwWmpsaUxUbGpZVFl0TlRVd01UWTVaV1E0TXpBMUxUWXdOVGd0T1dFNFpUQXdZVGN4WmpObFpqSmtaR0ZrTVRKbE1qSmlaR0l5TVROaE16QmhaamN5WkdFMU55SXNJbU5zYVdWdWRFbGtJam9pYzJWemMybHZiaTF6WlhKMmFXTmxMWFl4TGpBaUxDSndZMTl0WVhoZmNtRjBhVzVuWDIxdmRtbGxJam9pSWl3aVkyOXVkR1Y0ZENJNmUzMHNJblpsY21sbWFXTmhkR2x2Ymt4bGRtVnNJam96TENKbGVIQWlPakUxTWpNME5UYzRNekFzSW5SNWNHVWlPaUpWYzJWeUlpd2lhV0YwSWpveE5Ea3hPVEl4T0RNd0xDSjFjMlZ5YVdRaU9pSTBZVGhqWmpFMk5DMDVOV0k1TFRSbU9XSXRPV05oTmkwMU5UQXhOamxsWkRnek1EVXROakExTlMxak1qWmpNbUZqWmpjeFpURXlZbVZtWlRSbVlXTmlZalEyWW1abE9EZ3hZVGhoTlRobVpqQmhJaXdpZG1WeWMybHZiaUk2SW5ZeExqQWlMQ0p3WTE5dFlYaGZjbUYwYVc1blgzUjJJam9pSWl3aVpXMWhhV3dpT2lKcWFHVnVaSEpwZURFelFHZHRZV2xzTG1OdmJTSjkubHZyaktXaXhTakR6SEZoelFqVXFOS0hEZXRtUTMtOVczejhGaXpJTG9iQSJ9';
$bearer_token = '94vDO2IN1y963U8NO9Jw8omaG5q94Rht1ERjD6AEnKna90x04lf5Ty6brFsbYs8V';

preg_match('~\/media\/(.*)~', $url, $a);
preg_match('~\/events\/(.*)~', $key_url, $b);

$short_url = $a[0];
$short_key_url = $b[0];

if($video_url){
    if($url){
        $token = getToken($bearer_token, $base_token, $video_url);
        $stream = getStream($url, $short_url, $video_url, $token);
        
        $stream['tracking']['conviva']['fguid'] = '';
        $stream['tracking']['conviva']['userid'] = '';
        $stream['tracking']['conviva']['conid'] = '';
        
        $o = array('token' => $token, 's' => $stream);

        echo json_encode($o, JSON_UNESCAPED_SLASHES);
    }else if($key_url && $token_provided){
        $key = getKey($key_url, $short_key_url, $token_provided);
        
        $keyUTF8 = iconv('ISO-8859-1', 'utf-8', $key);
        
        $o = array('k' => $keyUTF8);
        
        echo json_encode($o, JSON_UNESCAPED_SLASHES);
    }
}

function getToken($bearer_token, $base_token, $video_url){
    $headers = explode("\n", 'POST /token HTTP/1.1
Host: global-api.live-svcs.mlssoccer.com
Connection: keep-alive
Content-Length: 959
accept: application/json
Origin: https://live.mlssoccer.com
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36
authorization: Bearer '. $bearer_token .'
content-type: application/x-www-form-urlencoded
Referer: '. $video_url .'
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.8');
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://global-api.live-svcs.mlssoccer.com/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => 'grant_type=refresh_token&latitude=0&longitude=0&platform=browser&token='. $base_token,
        CURLOPT_ENCODING => 'gzip, deflate, br'
    ));
    $r = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($r, true);
    return $data['access_token'];
}

function getStream($url, $short_url, $video_url, $token){
    $headers = explode("\n",'GET '. $short_url .' HTTP/1.1
Host: global-api.live-svcs.mlssoccer.com
Connection: keep-alive
accept: application/vnd.media-service+json; version=1
Origin: https://live.mlssoccer.com
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36
authorization: '. $token .'
Referer: '. $video_url .'
Accept-Encoding: gzip, deflate, sdch, br
Accept-Language: en-US,en;q=0.8');
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => 'gzip, deflate, sdch, br'
    ));
    $r = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($r, true);
    return $data;
}

function getKey($url, $short_key_url, $token){
    $headers = explode("\n", 'GET '. $short_key_url .' HTTP/1.1
Host: drm-api.live-svcs.mlssoccer.com
Connection: keep-alive
Content-Type: text/plain; charset=UTF-8
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36
Origin: https://live.mlssoccer.com
Authorization: '. $token .'
Accept: */*
Referer: '. $url .'
Accept-Encoding: gzip, deflate, sdch, br
Accept-Language: en-US,en;q=0.8');
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => 'UTF-8',
        CURLOPT_RETURNTRANSFER => true
    ));
    
    $r = curl_exec($ch);
    curl_close($ch);
    
    return $r;
}