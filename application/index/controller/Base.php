<?php
/**
 * Created by PhpStorm.
 * User: HF
 * Date: 2018/4/3
 * Time: 16:30
 */

namespace app\index\controller;


use think\Controller;
use think\Request;

class Base extends Controller {

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

    }

}