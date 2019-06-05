<?php
return [
    //短信类型，支持 local | qcloudsms | dysms
    'sms_type'  => env('app.sms_type', 'local'),
    //本地调试
    'local'     => [
        //本地万能验证码
        'public_verify_code' => env('sms:local.public_verify_code', '666666'),
        'sms_template_list'  => [], //短信模板
    ],
    //腾讯云短信
    'qcloudsms' => [
        'access_key_id'     => env('sms:qcloudsms.access_key_id'),
        'access_key_secret' => env('sms:qcloudsms.access_key_secret'),
        //副签名，留空取控制台的默认主签名，不留空时必须是腾讯云上通过认证的短信签名，通常情况下留空即可
        'sign_name'         => env('sms:qcloudsms.sign_name', ''),
        'sms_template_list' => [], //短信模板
    ],
    //阿里大鱼短信
    'dysms'     => [
        'access_key_id'     => env('sms:dysms.access_key_id'),
        'access_key_secret' => env('sms:dysms.access_key_secret'),
        //短信签名，必须和控制台的保持一致
        'sign_name'         => env('sms:dysms.sign_name'),
        'domain'            => env('sms:dysms.domain', 'dysmsapi.aliyuncs.com'),
        'region_id'         => env('sms:dysms.region_id', 'cn-hangzhou'),
        'action'            => env('sms:dysms.action', 'SendSms'),
        'version'           => env('sms:dysms.version', '2017-05-25'),
        'sms_template_list' => [], //短信模板
    ],
    //创蓝短信
    'clsms'     => [
        'access_key_id'     => env('sms:clsms.access_key_id'),
        'access_key_secret' => env('sms:clsms.access_key_secret'),
        //短信签名，创蓝本身不需要
        'sign_name'         => env('sms:clsms.sign_name'),
        'sms_template_list' => [], //短信模板
    ],
    //虹腾短信(需要报备ip和提前报备短信签名)
    'htsms'     => [
        'access_key_id'     => env('sms:htsms.access_key_id'),
        'access_key_secret' => env('sms:htsms.access_key_secret'),
        'cid'               => env('sms:htsms.cid', '3013'),
        'key'               => env('sms:htsms.key'),
        'operator'          => env('sms:htsms.operator', '10086'),
        'sign_name'         => env('sms:htsms.sign_name'),
        'sms_template_list' => [], //短信模板
    ]

];
/*
 * 注意事项：
 * sms_template_list 不为空时，use_cache，cache_key_prefix_sms_template和table_sms_template 3项配置均无效，
 * sms_template_list 格式为：
 * [
 *  'register'=>[
 *      'title'=>'用户注册',
 *      'template_id'=>'local_register',
 *      'params'=>['code'], //如果是腾讯云记得为 ['{1}'] 这种,并且是以1为开始而不是0开始
 *      'template'=>'您好，欢迎注册超级商城，您的手机验证码是：${code}，若非本人操作，请忽略！'
 *      // 腾讯云的则为： '您好，欢迎注册超级商城，您的手机验证码是：{1}，若非本人操作，请忽略！'
 *   ]
 * ]
 */