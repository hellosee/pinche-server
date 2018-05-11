<?php
/**
 * Created by PhpStorm.
 * User: lishoujie
 * Date: 2018/4/15
 * Time: 上午10:40
 */

namespace app\index\controller;


class Error extends Base
{
    public function index(){
        echo '访问的控制器不存在';
    }

}