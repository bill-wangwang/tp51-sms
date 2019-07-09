<?php

namespace tp51\sms\driver;

class HtSms extends Base {

    public function sendSms($config, $type, $mobile, $params) {

        $template = $this->getTemplateParams($config, 'htsms', $type, $params);
        $access_key_id = $config['access_key_id'] ?: '';
        $access_key_secret = $config['access_key_secret'] ?: '';
        $sign_name = $config['sign_name'] ?: '';
        $cid = $config['cid'] ?: '';
        $key = $config['key'] ?: '';
        $operator = $config['operator'] ?: '';
        $call_back_url = $config['call_back_url'] ?: '';
        $template_message = $template['template'];
        foreach ($params as $k => $value) {
            $template_message = str_replace('${' . $k . '}', $params[$k], $template_message);
        }
        if (empty($access_key_id) || empty($access_key_secret) || empty($sign_name) || empty($key)) {
            throw new \Exception("暂未配置({$config['sms_type']})短信的授权、短信签名等参数", $config['exception_code']);
        }
        try {
            $url = 'http://39.108.43.124:9092/SMS/signText/SMS/';
            $order = $this->_getOrderNumber();
            $sms_text = '【' . $sign_name . '】' . $template_message;
            if (is_string($mobile)) {
                $mobile = [$mobile];
            }
            //计算签名
            $sign = md5("channelCallbackUrl={$call_back_url}&channelOrderId={$order}&cid={$cid}&number=[" . implode(',', $mobile) . "]&operator={$operator}&key={$key}");
            //请求负载
            $data = [
                'channelCallbackUrl' => $call_back_url,
                'channelOrderId'     => $order,
                'cid'                => $cid,
                'number'             => $mobile,
                'operator'           => $operator,
                'sendText'           => $sms_text
            ];
            //请求头
            $headers = [
                'Content-Type:application/json',
                'Authorization:' . $sign ,
                'charset:UTF-8'
            ];
            $param = json_encode($data, JSON_UNESCAPED_UNICODE);
            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);  //抓取指定网页
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                $errorMessage = serialize(curl_error($ch));
                curl_close($ch);
                throw new \Exception("发送短信失败,网络错误({$errorMessage})", $config['exception_code']);
            }
            curl_close($ch);
            if(is_null($res) || empty($res)){
                throw new \Exception("发送短信失败,网络错误(001)", $config['exception_code']);
            }
            $json = json_decode($res, 1);
            if($json && is_array($json)){
                if(isset($json['code']) && $json['code']===200){
                    return $json['order_id'];
                } else {
                    throw new \Exception("发送短信失败,网络错误(003){$res}", $config['exception_code']);
                }
            } else {
                throw new \Exception("发送短信失败,网络错误(004){$res}", $config['exception_code']);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            throw new \Exception("发送短信失败(004) {$error}", $config['exception_code']);
        }
    }

    private function _getOrderNumber($pre = 'OR') {
        return $pre . date('YmdHis') . rand(1000, 9999);
    }

}