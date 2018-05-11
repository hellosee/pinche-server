<?php
namespace app\index\controller;

use think\captcha\Captcha;
use think\Request;

class Index extends Base {

    public function __construct(Request $request = null) {
        parent::__construct($request);
    }


}
