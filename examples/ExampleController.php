<?php

namespace app\index\controller;

use tp51\sms\Sms;

class ExampleController {

    public function index() {
        $sms = new Sms();
        $type = 'register'; //短息模板类型标识 如 register | find_password
        $mobile = '13800138000'; //手机号
        //系统内置了获取验证码方法，可以配置 验证码的长度，有效期，有效时间内获取的同一个手机号的同一类型的验证码是否一样
        $code = $sms->getVerifyCode($type, $mobile);
        //发送短信
        $out_id = $sms->send($type, $mobile, ['code'=>$code]);

        //验证
        $input_code = input('param.code'); //用户输入的验证码
        $res = $sms->checkVerifyCode($type, $mobile, $input_code);
        if($res){
            //验证成功了
            //这里可以写具体的业务逻辑
            echo 'success';
        } else {
            //验证失败了
            echo 'error';
        }
    }
}