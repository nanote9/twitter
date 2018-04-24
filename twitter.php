<?php

///////////////////////////////////////
//
//	  twitteroauth
//
///////////////////////////////////////

echo 'START' . "\r\n";

// オートローダーを起動
require_once( './vendor/autoload.php' );

// キーの設定
define('CONSUMER_KEY', '');
define('CONSUMER_SECRET', '');
define('ACCESS_TOKEN', '');
define('ACCESS_TOKEN_SECRET', '');
define('IMEGES_NUM', 10);
define('TRY_NUM', 1000);

// 接続
$connection = new Abraham\TwitterOAuth\TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

// 画像取得
function get_imges($connection, array $param, array &$images){
	$tweets = $connection->get('search/tweets', $param)->statuses;
	// フォロワーのIDを取得してみる
	foreach ($tweets as $tweet) {
		if (isset($tweet->extended_entities->media) && is_array($tweet->extended_entities->media)) {
			foreach($tweet->extended_entities->media as $key => $media) {
				if (!empty($media->media_url) &&  $type = check_ext($media->media_url)) {
					$images[$media->media_url] = ['url' => $media->media_url, 'ext' => $type];
					if (count($images) >= IMEGES_NUM) {
						return true;
					}
				}
			}
		}
	}
	
	$num = count($images);
	if ($num >= IMEGES_NUM) {
		return true;
	} elseif($param["count"] >= TRY_NUM) {
		return false;
	} else {
		$param["count"] += IMEGES_NUM;
		get_imges($connection, $param, $images);
	}
	
}

// 拡張子確認
function check_ext(string $url){
	$type = exif_imagetype($url);
    switch($type){
        case IMAGETYPE_GIF:
        	$ext = '.gif';
        	break;
        case IMAGETYPE_JPEG:
        	$ext = '.jpg';
        	break;
        case IMAGETYPE_PNG:
        	$ext = '.png';
        	break;
        //どれにも該当しない場合
        default:
        	$ext = false;
        	break;
    }
    return $ext;
}

$param = array(
    "q"=> 'filter:images AND JustinBieber',
    "count"=>IMEGES_NUM,
    "result_type"=>"recent",
    "include_entities"=>true
);
$images = [];
get_imges($connection, $param, $images);

$dir_path = './img/';
if(!file_exists($dir_path ) ){
	mkdir($dir_path, 0755);
}

// ファイルの削除
foreach(glob($dir_path . '*') as $file){
	unlink($file);
}

$i = 0;
foreach ($images as $image) {
	$type = exif_imagetype($image['url']);
    switch($type){
        case IMAGETYPE_GIF:
        	$ext = '.gif';
        	break;
        case IMAGETYPE_JPEG:
        	$ext = '.jpg';
        	break;
        case IMAGETYPE_PNG:
        	$ext = '.png';
        	break;
        //どれにも該当しない場合
        default:
        	continue;
        	break;
    }
    $data = file_get_contents($image['url']);
    file_put_contents($dir_path . $i . $image['ext'] ,$data);
    $i++;
}

echo 'FINISHED';