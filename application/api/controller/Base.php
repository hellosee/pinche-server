<?php
/**
 * Created by PhpStorm.
 * User: HF
 * Date: 2018/4/3
 * Time: 16:30
 */

namespace app\api\controller;


use think\Controller;
use think\Request;
use think\Session;

class Base extends Controller {

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        Session::start();

    }
    public function _empty(){
        $this->error('方法不存在');
    }
    public function getSign( $params  = "" ){
        $sign = "";
        ksort($params);
        foreach( $params as $key => $value ){
            if( $value != "" ){
                $sign .= $key . "=" . $value . "&";
            }
        }
        $sign .= "key=" . SECRET_KEY;
        return strtoupper(md5($sign));
    }


}