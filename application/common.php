<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 打印输出
 * @param array $obj
 * @param int $exit
 */
function p($obj = array(),$exit = 0,$format=1){
    if($format){
        echo "<pre>";
        print_r($obj);
        echo "</pre>";
    } else {
        print_r($obj);
    }
    $exit && exit;
}
function output($data = NULL, $http_code = NULL){
    $json = json_encode($data);
    exit($json) ;
}

/*
 * 字符串防SQL注入编码，对GET,POST,COOKIE的数据进行预处理
 * @param  $input 要处理字符串或者数组
 * @param  $urlencode 是否要URL编码
 */
function escape($input, $urldecode = 0) {
    if(is_array($input)){
        foreach($input as $k=>$v){
            $input[$k]=escape($v,$urldecode);
        }
    }else{
        $input=trim($input);
        if ($urldecode == 1) {
            $input=str_replace(array('+'),array('{addplus}'),$input);
            $input = urldecode($input);
            $input=str_replace(array('{addplus}'),array('+'),$input);
        }
        // PHP版本大于5.4.0，直接转义字符
        if (strnatcasecmp(PHP_VERSION, '5.4.0') >= 0) {
            $input = addslashes($input);
        } else {
            // 魔法转义没开启，自动加反斜杠
            if (!get_magic_quotes_gpc()) {
                $input = addslashes($input);
            }
        }
    }
    //防止最后一个反斜杠引起SQL错误如 'abc\'
    if(substr($input,-1,1)=='\\') $input=$input."'";//$input=substr($input,0,strlen($input)-1);
    return $input;
}

//处理XSS，$input=$_COOKIE,$_GET,$_POST
function sqlxss($input){
    if(is_array($input)){
        foreach($input as $k=>$v){
            $k=sqlxss($k);
            $input[$k]=sqlxss($v);
        }
    }else{
        $input=escape($input,1);
        $input=htmlspecialchars($input,ENT_QUOTES);
    }
    return $input;
}

// 将时间转换成几分钟前
function time_tran($the_time) {
    $now_time = date("Y-m-d H:i:s", time());
    $now_time = strtotime($now_time);
    $show_time = strtotime($the_time);
    $dur = $now_time - $show_time;
    if ($dur < 0) {
        return $the_time;
    } else {
        if ($dur < 60) {
            return $dur . '秒前';
        } else {
            if ($dur < 3600) {
                return floor($dur / 60) . '分钟前';
            } else {
                if ($dur < 86400) {
                    return floor($dur / 3600) . '小时前';
                } else {
                    if ($dur < 259200) {//3天内
                        return floor($dur / 86400) . '天前';
                    } else {
                        return date('m-d',$show_time);
                    }
                }
            }
        }
    }
}

/**
 * 生成永远唯一的激活码
 * @return string
 */
function create_guid($namespace = null) {
    static $guid = '';
    $uid = uniqid ( "", true );

    $data = $namespace;
    $data .= $_SERVER ['REQUEST_TIME'];     // 请求那一刻的时间戳
    $data .= $_SERVER ['HTTP_USER_AGENT'];  // 获取访问者在用什么操作系统
    $data .= $_SERVER ['SERVER_ADDR'];      // 服务器IP
    $data .= $_SERVER ['SERVER_PORT'];      // 端口号
    $data .= $_SERVER ['REMOTE_ADDR'];      // 远程IP
    $data .= $_SERVER ['REMOTE_PORT'];      // 端口信息

    $hash = strtoupper ( hash ( 'ripemd128', $uid . $guid . md5 ( $data ) ) );
    $guid = substr ( $hash, 0, 8 ) .substr ( $hash, 8, 4 ) . substr ( $hash, 12, 4 ) . substr ( $hash, 16, 4 ) .  substr ( $hash, 20, 12 ) ;
    return $guid;
}

/**
 * 生成登录安全码
 */
function security_code($length = 8,$type = 'numstr') {
    $source = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()_+-=';
    if($type=='number') $source='0123456789';
    if($type=='numstr') $source='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($source);
    $return = '';
    for ($i = 0; $i < $length; $i++) {
        $index = rand() % $len;
        $return .= substr($source, $index, 1);
    }
    return $return;
}

// 判断密码
function verify_upass($str) {
    if (strlen($str) >= 6 && strlen($str) <= 20) {
        return '';
    } else {
        return '密码长度为6-20个字符';
    }
    if(strstr($str,"'")) return '密码不能有单引号';
}

// 判断手机号码
function verify_mobile($str) {
    if( preg_match("~^((\(\d{3}\))|(\d{3}\-))?(13\d{9}|15\d{9}|17\d{9}|18\d{9}|14\d{9})$~", $str)){
        return '';
    }else{
        return '手机号码不正确';
    }
}

// 判断用户名
function verify_uname($str) {
    $str=strtolower($str);
    $no_prefix=array('sys','manage','admin');
    foreach($no_prefix as $v){
        if(substr($str,0,strlen($v))==$v) return '不允许 '.implode(' , ',$no_prefix).' 开头';
    }
    if(!preg_match('~^[a-z][a-z0-9_]{5,19}$~', $str)) return '6～20个字符，字母开头，字母、数字组成'; /*return '用户名长度6～20个字符，以字母a～z（不区分大小写）开头，且只能由字母、数字0～9和下划线组成';*/
    return '';
}


function parse_string($s) {
    if (is_array($s)) {
        return $s;
    }
    parse_str($s, $r);
    return $r;
}

function merge_string($a) {
    if (!is_array($a) && !is_object($a)) {
        return (string) $a;
    }
    return http_build_query(to_array($a));
}
function to_array($a) {
    $a = (array) $a;
    foreach ($a as &$v) {
        if (is_array($v) || is_object($v)) {
            $v = to_array($v);
        }
    }
    return $a;
}

function uuid($prefix = '',$f = '') {
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8) . $f;
    $uuid .= substr($chars,8,4) . $f;
    $uuid .= substr($chars,12,4) . $f;
    $uuid .= substr($chars,16,4) . $f;
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}

/**
 * 判断当前是否是微信浏览器
 */
function isWeixin()
{

    if (strpos($_SERVER['HTTP_USER_AGENT'],

            'MicroMessenger') !== false) {

        return 1;
    }

    return 0;
}

/**
 * 可逆加密
 *
 * @param  $txtStream 要加密的字符串
 * @param  $password 加密私钥=解密私钥
 */
function encrypt($txtStream) {
    // 随机找一个数字，并从密锁串中找到一个密锁值
    $lockstream=LOCK_STREAM;
    $password = P_PASS;
    $lockLen = strlen($lockstream);
    $lockCount = rand(0, $lockLen-1);
    $randomLock = $lockstream[$lockCount];
    // 结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    // 开始对字符串加密
    $txtStream = base64_encode($txtStream);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k = $k == strlen($password) ? 0 : $k;
        $j = (strpos($lockstream, $txtStream[$i]) + $lockCount + ord($password[$k])) % ($lockLen);
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return $tmpStream . $randomLock;
}

/**
 * 可逆解密
 *
 * @param  $txtStream 要解密的字符串
 * @param  $password 解密私钥=加密私钥
 */
function decrypt($txtStream) {
    $lockstream=LOCK_STREAM;
    $password = P_PASS;
    $lockLen = strlen($lockstream);
    // 获得字符串长度
    $txtLen = strlen($txtStream);
    // 截取随机密锁值
    $randomLock = $txtStream[$txtLen - 1];
    // 获得随机密码值的位置
    $lockCount = strpos($lockstream, $randomLock);
    // 结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    // 开始对字符串解密
    $txtStream = substr($txtStream, 0, $txtLen-1);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k = $k == strlen($password) ? 0 : $k;
        $j = strpos($lockstream, $txtStream[$i]) - $lockCount - ord($password[$k]);
        while ($j < 0) {
            $j = $j + ($lockLen);
        }
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return base64_decode($tmpStream);
}
//获取远程内容
function geturl($url) {
    $headers1 = array(
        //'referer' => $_POST['referer'],
        'Client-IP' => (empty($_SERVER['HTTP_CLIENT_IP'])? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_CLIENT_IP']),
        'X-Forwarded-For' => (empty($_SERVER['HTTP_X_FORWARDED_FOR'])? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR']),
    );
    // 判断是否支持CURL
    if (!function_exists('curl_init') || !function_exists('curl_exec')) {
        exit('您的主机不支持Curl，请开启~');
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Cloud Parse');
    //curl_setopt($curl, CURLOPT_REFERER, "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}


/**
 * 获取url参数
 *
 * @param unknown $action
 * @param string $param
 */
function __URL($url, $param = '') {
    return url($url) .'?'. $param;
}

function url_model()
{
    return 1;
}

/**
 * Encode array to utf8 recursively
 * @param $dat
 * @return array|string
 */
function array_utf8_encode($dat)
{
    if (is_string($dat))
        return utf8_encode($dat);
    if (!is_array($dat))
        return $dat;
    $ret = array();
    foreach ($dat as $i => $d)
        $ret[$i] = array_utf8_encode($d);
    return $ret;
}