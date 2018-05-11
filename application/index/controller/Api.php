<?php
/**
 * Created by PhpStorm.
 * User: lishoujie
 * Date: 2018/4/8
 * Time: 下午10:44
 */

namespace app\index\controller;
use think\Request;

/**
 *
 * Class Api
 * @package app\index\controller
 */

class Api extends Base {

    //视频类型
    private $type = "";
    //防盗链域名，多个用|隔开，如：123.com|abc.com（不会设置盗链请留空）
    private $referer_url = '';
    //用户授权UID，在 http://www.odflv.com/user.jsp 平台可以查看到
    private $user_id = '80048460';
    //用户授权Token，在 http://www.odflv.com/user.jsp 平台可以查看到
    private $user_token = '01ccfd434aaf35493b17be661d929a06';
    //视频默认清晰度，1标清，2高清，3超清，4原画，如果没有高清会自动下降一级
    private $vod_hd = 3;
    //配置百度的cookies信息，不会获取的请加交流群 488031388 在群文件里面看获取教程
    private $bduss = '';
    //配置百度的SToken，不会获取的请加交流群 488031388 在群文件里面看获取教程
    private $bdstoken = '';
    private $times = 0;
    private $key = "";
    private $api_url = "http://api.odflv.com/newparse";



    private $retData = [];

    public function __construct(Request $request = null) {
        parent::__construct($request);
        //判断来源。如果来源为空，则提示错误。
        $this->times = time();
        $this->key = md5($this->user_id.$this->_webPath().$this->referer_url);

    }

    /**
     * 解析首页传过来的url播放
     * @param Request $request
     */
    public function index(Request $request){
        $retData = [];

        $playUrl = $request->post("playUrl","","rawurlencode");
        if(empty($playUrl)){
            $this->error("URL地址不能为空");
        }

        if(strstr($playUrl,'v.youku.com')){
            $this->type = 'youku';
        } elseif(strstr($playUrl,'v.qq.com')){
            $this->type = 'qq';
        } elseif(strstr($playUrl,'iqiyi.com')){
            $this->type = 'iqiyi';
        }

        $key = md5($this->times.$this->key);
        //判断手机客户端
        $wap = preg_match("/(iPhone|iPad|iPod|Linux|Android)/i", strtoupper($_SERVER['HTTP_USER_AGENT']));

        if( $this->type == "baidupan" ){
            //组装清晰度切换URL
            $param_url='key='.md5($this->times.$key).'&time='.$this->times.'&url='.$playUrl.'&type='.$this->type.'&xml=1'.'&BDUSS='.$this->bduss.'&STOKEN='.$this->bdstoken;
            //组装URL参数
            $param = 'token='.$this->user_token.'&url='.$playUrl.'&type='.$this->type.'&hd='. $this->vod_hd.'&wap='.$wap.'&BDUSS='.$this->bduss.'&STOKEN='.$this->bdstoken;

        } else {
//组装清晰度切换URL
            $param_url='key='.md5($this->times.$this->key).'&time='.$this->times.'&url='.$playUrl.'&type='.$this->type.'&xml=1';

            //组装URL参数
            if(in_array($this->type, array('youku','iqiyi','qq'))){
                $param = 'token='.$this->user_token.'&url='.$playUrl.'&type='.$this->type.'&hd='. $this->vod_hd.'&wap='.$wap.'&ext=ajax';
                if($this->type == 'iqiyi') $param = $param."&cupid=qc_100001_100102";
            } else {
                $param = 'token='.$this->user_token.'&url='.$playUrl.'&type='.$this->type.'&hd='. $this->vod_hd.'&wap='.$wap;
            }

        }

        $json = $this->_geturl($this->api_url."?uid=".$this->user_id . "&" .$param);

        $arr = json_decode($json,1);

        if($arr['success'] != 1){
            $this->error("解析出错啦，请稍后重试或者在求片留言区提交留言给我们，谢谢。",'',[]);
        }

        $retData['success'] = $arr['success'];
        $retData['type'] = $this->type;
        $retData['title'] = "";
        $retData['play'] = $arr['ext'] == 'mp4' ? 'xml' : $arr['ext'];
        $retData['url'] = $arr['url'];
        // 更新或添加时间(2017.11.16)(行数：下1行)
        $retData['url'] = (strstr($retData['play'], 'm3u8') && strstr($retData['url'], '/files/'))? "/api/mu?".merge_string(parse_url($retData['url'])): $retData['url'];

        if(!$wap){
            if($arr['ext']=='m3u8_list'){ //M3U8列表
                $retData['url'] = rawurlencode('/api/index?'.str_replace('&xml=1','&m3u8=1',$param_url));
                $retData['play'] = 'm3u8';
            }elseif($arr['ext']=='m3u8'){ //M3U8
                // 更新或添加时间(2017.11.16)(行数：下4行)
                $retData['url'] = rawurlencode($retData['url']);
            }elseif($arr['ext']=='hls_m3u8' || $arr['ext']=='hls'){ //M3U8
                $retData['play'] = 'hls';
                $retData['url']  = $retData['url'];
            }elseif($retData['play'] == 'xml'){ //PC XML
                $retData['url'] = '/api/index?'.$param_url;
            }
        }else {
            if ($arr['ext'] == 'm3u8_list') {
                $retData['url'] = '/api/index?' . str_replace('&xml=1', '&m3u8=1', $param_url);
            }
        }
        $preg = '~<title>(.*?)</title>~';
        //查询该网址是否能否获取到title
        $content = @file_get_contents(rawurldecode($playUrl));
        if(preg_match_all($preg, $content, $Arr)){
            if(!empty($Arr[1])){
                $retData['title'] = $Arr[1][0];
            }
        }

        $this->success('获取成功','',$retData);

    }


    public function mu(Request $request){

        $query = $request->get('query','');
        $scheme = $request->get("scheme",'');
        $host = $request->get("host",'');
        $path = $request->get("path",'');
        $scheme = $request->get("scheme",'');

        $query = parse_string(str_replace("&amp;", "&", $query));
        if($query == false){
            exit('404');
        }
        $get['purl'] = $scheme."://".$host.$path."?".merge_string($query);

        $str_m3u8 = curl($get['purl']);
        if(substr($str_m3u8,0,7) != '#EXTM3U') exit('404');

        header('Content-Type: application/vnd.apple.mpegurl');
        header('Content-disposition: attachment; filename=video.m3u8');
        if(strstr($str_m3u8,'data.vod.itc.cn')){
            // $str_m3u8 = str_replace("http://", "/api/url?path=", $str_m3u8);
        }
        echo $str_m3u8;
    }

    public function url(Request $request){
        $path = $request->get('path','');
        $url = "http://".$path;
        unset($path);
        $location = $url.'&'.merge_string($_GET);
        if(strstr($location,'data.vod.itc.cn')){
            $suid = play_verify();
            $location = str_replace(strzhong($location,"&uid=","&"), $suid[0], $location);
            $location = str_replace(strzhong($location,"&SOHUSVP=","&"), $suid[1], $location);
        }
        header('HTTP/1.1 301 Moved Permanently');
        Header("Location: {$location}");

    }

    public function xml(Request $request){
        $get = $_GET;
        if($get['a'] == 'setswf'){
            header("Content-Type: text/xml");
            $get = parse_string(base64_decode($get['data']));
            if(!isset($get['playtype'])){
                $get['playtype'] = isset($get['stype']) ? $get['stype'] : "";
            }
            if($get['site'] == 'acfun'){
                $hds = array(1=>1,2=>2,3=>3);
            } else {
                $hds = array(1=>'mp4hd',2=>'mp4hd2',3=>'mp4hd3');
            }
            $hdb = array('mp4hd3' => 3,'mp4hd2' => 2,'mp4hd' => 1);
            foreach ($hds as $key => $value) {
                $defa[$key] = "/api/xml?a=setswf&data=".base64_encode("ccode=".$get['ccode']."&vid=".$get['vid']."&site=".$get['site']."&playtype=".$get['playtype']."&sign=".$get['sign']."&stype=".$value."&weparser_swf_url=".$get['weparser_swf_url']);
            }
            $xml='<ckplayer><flashvars><![CDATA[{s->3}{h->3}{f->'.$get['weparser_swf_url'].'}{a->'.$defa[$hdb[$get['stype']]].'}{defa->'.implode('|',$defa).'}';
            $xml.='{deft->标清|高清|超清}{site->'.$get['site'].'}{playtype->'.$get['playtype'].'}{sign->'.$get['sign'].'}{vid->'.$get['vid'].'}{stype->'.$get['stype'].'}{ccode->'.$get['ccode'].'}';
            $xml.=']]></flashvars>';
            $xml.='<videos><file><![CDATA[]]></file></videos>';
            $xml.='</ckplayer>';
            $xml='<?xml version="1.0" encoding="utf-8"?>'.$xml;
            exit($xml);
        } elseif($get['a'] == 'setxml'){
            header("Content-Type: text/xml");
            $xml  = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '<ckplayer>';
            $xml .= '<flashvars>{f-><![CDATA['.base64_decode($get['url']).']]>}</flashvars>';
            $xml .= '<video>';
            $xml .= '<file><![CDATA['.base64_decode($get['url']).']]></file>';
            $xml .= '<size><![CDATA[0]]></size>';
            $xml .= '<seconds><![CDATA[0]]></seconds>';
            $xml .= '</video>';
            $xml .= '</ckplayer>';
            exit($xml);

        } else if(substr($get['a'],0,4) == 'm3u8'){
            header('Content-disposition: attachment; filename=playm3u8.m3u8');
            $get = parse_string(base64_decode(substr($get['a'],4,strlen($get['a']))));
            $m3u8 = $this->_geturl($get['url']);
            preg_match("|http://(.*?)\/|", $m3u8, $host);
            $m3u8 = str_replace('http://'.$host[1],"/ts?skuid=".$host[1],$m3u8);
            exit($m3u8);
        }

    }
    public function ts(Request $request){
        header("Content-Type: application/octet-stream;charset=utf-8");
        $skuid=$_GET['skuid'];
        $skuid?$skuid:exit('skuid');
        if(strstr($skuid,'qiyi.com')){
            $get = $_GET;
            $url = "http://".$get['skuid'];
            unset($get['skuid']);
            $url = $url.'&'.merge_string($get);
        } else {
            $url=convert_uudecode($this->base64url_decode($skuid));
        }
        header('Location:'.$url);
        $url=null;
        exit();
    }
    //自定义64加密
    public function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '_-'), '=');
    }

    //自定义64解密
    public function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '_-', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    //判断防盗链域名
    private function is_referer(){
        //没有设置防盗链
        if($this->referer_url=='') return true;
        //获取来路域名
        $uriarr = parse_url($_SERVER['HTTP_REFERER']);
        $host = $uriarr['host'];
        $ymarr = explode("|",$this->referer_url);
        if(in_array($host,$ymarr)){
            return true;
        }
        return false;
    }

    private function _webPath(){
        //$uri = 'http://odflv'.$_SERVER['REQUEST_URI'];
        //$arr = parse_url($uri);
        //return str_replace(SELF,'',$arr['path']);
        return "/";
    }

    private function _geturl($url){
        $headers1 = array(
            'referer' => $this->referer_url,
            'Client-IP' => (empty($_SERVER['HTTP_CLIENT_IP'])? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_CLIENT_IP']),
            'X-Forwarded-For' => (empty($_SERVER['HTTP_X_FORWARDED_FOR'])? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR']),
        );
        $url = $url.'&lref='.rawurlencode($_SERVER['HTTP_REFERER'])."&headers1=".rawurlencode(merge_string($headers1))."&ver=100&User-Agent=".base64_encode(base64_encode($_SERVER['HTTP_USER_AGENT']));
        // 判断是否支持CURL
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            exit('您的主机不支持Curl，请开启~');
        }
        //die($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Cloud Parse');
        curl_setopt($curl, CURLOPT_REFERER, "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
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

    //数组转XML
    private function _xml($str,$param){
        global $hd;
        $param = str_replace('&','&amp;',$param);
        $xml='<ckplayer><!-- ODFLV视频解析群 642125668--><flashvars>{lv->0}{v->80}{e->0}{p->1}{q->start}{h->3}{f->'.'/api/index?'.$param.'&amp;[$pat]}{a->hd='.$hd.'}{defa->hd=1|hd=2|hd=3|hd=4}{deft->标清|高清|超清|原画}</flashvars>
    <video>';
        $arr = $str['url'];
        if(is_array($arr)){
            for($i=0;$i<count($arr);$i++){
                $xml.='<file><![CDATA['.$arr[$i]['purl'].']]></file>';
                if(isset($arr[$i]['size'])) $xml.='<size>'.$arr[$i]['size'].'</size>';
                if(isset($arr[$i]['sec'])) $xml.='<seconds>'.$arr[$i]['sec'].'</seconds>';
            }
        }else{
            $xml.='<file><![CDATA['.$str['url'].']]></file>';
            if(isset($str['size'])) $xml.='<size>'.$str['size'].'</size>';
            if(isset($str['sec'])) $xml.='<seconds>'.$str['sec'].'</seconds>';
        }
        $xml.='</video></ckplayer>';
        return $xml;
    }

}