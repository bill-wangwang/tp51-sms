<?php

namespace tp51\sms;

use think\Db;
use tp51\sms\driver\LocalSms;
use tp51\sms\driver\DySms;
use tp51\sms\driver\ClSms;
use tp51\sms\driver\HtSms;
use tp51\sms\driver\QcloudSms;

class Sms {

    //版本号
    private $version = '1.0.5';
    //发送成功后回调
    private $sendCallBack = null;
    //默认config配置
    protected $_config = [
        'exception_code'                => 8101, //异常错误代码
        'sms_type'                      => 'local', //短信类型 ['qcloudsms', 'dysms', 'local', 'clsms', 'htsms']
        'sms_template_list'             => [], //短信模板，该项不为空时以此为准，为空时用数据库中的模板
        'same_as_last_time'             => true, //验证码有效期时间内重新获取是否和上次一样 true | false
        'verify_code_length'            => 4, //验证码长度（分钟）
        'expiration_time'               => 10, //验证码有效期时间（分钟）
        'use_cache'                     => false, //短信模板是否使用缓存，生产环境建议改为true可以提高性能
        'cache_key_prefix_verify_code'  => 'sms_verify_code_', //验证码缓存key前缀
        'cache_key_prefix_sms_template' => 'sms_templte_',//短信模板缓存key前缀
        'table_sms_template'            => 'sms_template',//短信模板表名（不需要写database配置的前缀）
        'table_sms_log'                 => 'sms_log', //默认的发送成功后的短信日志表名（不需要写database配置的前缀） 留空则不写日志
        'max_try'                       => 5, //允许验证码尝试的最大错误次数
        'delete'                        => true, //验证成功后是否销毁验证码 true | false
        'invalid_code_message'          => '验证码无效或者已过期，请重新获取验证码', //无效验证码错误提示语
        'incorrect_message'             => '验证码错误', //验证码不正确提示语
        'too_many_times_message'        => '尝试错误次数过多，请重新获取验证码', //尝试次数过多错误提示语
    ];

    /*
     * 注意事项：
     * sms_template_list 不为空时，use_cache，cache_key_prefix_sms_template和table_sms_template 3项配置均无效，
     * sms_template_list 格式为：
     * [
     *  'register'=>[
     *      'title'=>'用户注册',
     *      'template_id'=>'local_register',
     *      'params'=>['code'], //如果是腾讯云记得为 ['{1}'] 这种
     *      'template'=>'您好，欢迎注册超级商城，您的手机验证码是：${code}，若非本人操作，请忽略！'
     *   ]
     * ]
     */

    public function __construct($config = []) {
        $tp_version = \think\App::VERSION;
        if(!$tp_version){
            $tp_version = '5.1.36';
        }
        if( version_compare($tp_version, '6.0.0')>=0 ){
            $this->_config = array_merge($this->_config, config('sms'), $config);
        } else {
            $this->_config = array_merge($this->_config, config('sms.'), $config);
        }
    }

    public function setSendCallBack($call_back) {
        $this->sendCallBack = $call_back;
    }

    private function _getSendCallBack() {
        return $this->sendCallBack;
    }

    public function setDelete($value) {
        $this->_config['delete'] = $value;
    }

    public function setVerifyCodeLength($value) {
        $this->_config['verify_code_length'] = $value;
    }

    public function setSmsTemplateList($value) {
        $this->_config['sms_template_list'] = $value;
    }

    public function setMaxTry($value) {
        $this->_config['max_try'] = $value;
    }

    public function getVersion() {
        return $this->version;
    }

    public function setSmsType($value) {
        $this->_config['sms_type'] = strtolower($value);
        //设置完顺便检查下类型是否允许
        $this->_getSmsType();
    }

    /**
     * 设置短信签名
     * @param $value string 短信签名
     */
    public function setSignName($value) {
        $this->_config['sign_name'] = $value;
    }

    /**
     * 清除短信模板缓存
     * @return bool
     */
    public function clearTemplatesCache() {
        $allow_sms_type = ['qcloudsms', 'dysms', 'local', 'clsms', 'htsms'];
        foreach ($allow_sms_type as $sms_type) {
            $cache_key = $this->_config['cache_key_prefix_sms_template'] . $sms_type;
            cache($cache_key, null);
        }
        return true;
    }

    private function _getSmsType() {
        $allow_sms_type = ['qcloudsms', 'dysms', 'local', 'clsms', 'htsms'];
        $sms_type = strtolower($this->_config['sms_type']);
        if (in_array($sms_type, $allow_sms_type)) {
            return $sms_type;
        } else {
            throw new \Exception("暂不支持{$sms_type}的短信类型", $this->_config['exception_code']);
        }
    }

    /**
     * 获取随机字符串
     * @param int $length 长度
     * @param string $codeSet 指定的字符串里面的随机数
     * @return string
     */
    protected function getRandStr($length = 4, $codeSet = "") {
        $codeSet = $codeSet ? $codeSet : "0123456789";
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $codeSet[mt_rand(0, strlen($codeSet) - 1)];
        }
        return $code;
    }

    /**
     * 发送短信
     * @param $type string 模板短信类型标识 如 register | find_password
     * @param $mobile string 11位的手机号码
     * @param array $params 发送本次短信需要的参数 如['code'=>1234] | [1234]
     * @return string 返回第三方的标识ID
     * @throws \Exception
     */
    public function send($type, $mobile, $params = []) {
        switch ($this->_config['sms_type']) {
            case 'dysms':
                $object = new DySms();
                break;
            case 'qcloudsms':
                $object = new QcloudSms();
                break;
            case 'clsms':
                $object = new ClSms();
                break;
            case 'htsms':
                $object = new HtSms();
                break;
            case 'local':
                $object = new LocalSms();
                break;
            default:
                throw new \Exception("暂未支持的短信类型-{$this->_config['sms_type']}", $this->_config['exception_code']);
                break;
        }
        //如果config/sms.php中的 [sms_type]['sms_template_list'] 不为空
        $merge_config = array_merge(config('sms.' . $this->_config['sms_type']), $this->_config);
        if (!$merge_config['sms_template_list'] && isset($merge_config[$this->_config['sms_type']]) && isset($merge_config[$this->_config['sms_type']]['sms_template_list']) && !empty($merge_config[$this->_config['sms_type']]['sms_template_list'])) {
            $merge_config['sms_template_list'] = $merge_config[$this->_config['sms_type']]['sms_template_list'];
        }
        $out_id = $object->sendSms($merge_config, $type, $mobile, $params);
        //回调函数的参数
        $callBackParams = [
            'sms_type' => $this->_config['sms_type'],
            'type'     => $type,
            'mobile'   => $mobile,
            'params'   => $params,
            'out_id'   => $out_id
        ];
        //如果有配置短信日志表则写日志
        if ($this->_config['table_sms_log']) {
            $this->_writeSmsLog($callBackParams);
        }
        //如果有设置回调方法并且是一个可以回调的方法
        $sendCallBack = $this->_getSendCallBack();
        if (!is_null($sendCallBack)) {
            if (is_callable($sendCallBack)) {
                call_user_func($sendCallBack, $callBackParams);
            } else {
                throw new \Exception(json_encode($sendCallBack) . "不是有效的回调函数", $this->_config['exception_code']);
            }
        }
        return $out_id;
    }

    /**
     * 写短信日志到数据
     * @param $data
     * @return int|string
     */
    private function _writeSmsLog($data) {
        $data['params'] = json_encode($data['params']);
        if (!isset($data['create_time'])) {
            $data['create_time'] = time();
        }
        return Db::table(config('database.prefix') . $this->_config['table_sms_log'])->insert($data);
    }

    /**
     * @param $type string 模板短信类型标识 如 register | find_password
     * @param $mobile string 手机号码
     * @return mixed|string 返回对应的验证码
     */
    public function getVerifyCode($type, $mobile) {
        //验证码key
        $cache_key = $this->_config['cache_key_prefix_verify_code'] . $type . '_' . $mobile;
        //验证码尝试错误次数key
        $cache_key_error = $cache_key . '_error';
        //如果开启有效时间内重复获取验证码返回同一个
        if ($this->_config['same_as_last_time']) {
            $code = cache($cache_key);
            if (!$code) {
                $code = $this->getRandStr($this->_config['verify_code_length']);
            }
        } else {
            //强制每次重新获取
            $code = $this->getRandStr($this->_config['verify_code_length']);
        }
        //每次获取后都把对应的尝试错误次数删掉
        cache($cache_key_error, null);
        cache($cache_key, $code, $this->_config['expiration_time'] * 60);
        return $code;
    }

    /**
     * 检验验证码
     * @param $type string 模板短信类型标识 如 register | find_password
     * @param $mobile string 手机号
     * @param $code string 验证码
     * @return bool
     * @throws VerifyCodeException
     * @throws \Exception
     */
    public function checkVerifyCode($type, $mobile, $code) {
        $cache_key = $this->_config['cache_key_prefix_verify_code'] . $type . '_' . $mobile;
        $answer = cache($cache_key);
        if (!$answer) {
            throw  new \Exception($this->_config['invalid_code_message'], $this->_config['exception_code']);
        }
        //如果是本地类型 并且配置通用验证码 强制重置 answer 为配置项的内容
        if ($this->_config['sms_type'] == 'local') {
            $temp = config('sms.local');
            if (isset($temp['public_verify_code']) && !empty($temp['public_verify_code'])) {
                $answer = $temp['public_verify_code'];
            }
        }
        $error_cache_key = $cache_key . '_error'; //错误次数的key
        if ($code == $answer) {
            if ($this->_config['delete']) {
                cache($cache_key, null);
                cache($error_cache_key, null);
            }
            return true;
        } else {
            $error_count = intval(cache($error_cache_key)) + 1;
            if ($error_count > $this->_config['max_try']) {
                cache($cache_key, null);
                cache($error_cache_key, null);
                throw  new \Exception($this->_config['too_many_times_message'], $this->_config['exception_code']);
            } else {
                cache($error_cache_key, $error_count);
                throw  new \Exception($this->_config['incorrect_message'], $this->_config['exception_code']);
            }
        }
    }
}