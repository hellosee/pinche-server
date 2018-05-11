<?php
/**
 * Created by PhpStorm.
 * User: HF
 * Date: 2018/5/11
 * Time: 15:45
 */

namespace app\api\controller;


use think\Request;

class User extends Base
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function register(Request $request){
        if($request->isAjax()){
            $type = $request->post('type',0);//用户类型1=司机，0=用户
            $


        }

    }


}