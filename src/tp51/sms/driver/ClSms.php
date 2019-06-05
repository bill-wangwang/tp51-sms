<?php

namespace tp51\sms\driver;

class ClSms extends Base {
    public function sendSms($config, $type, $mobile, $params) {
        $template = $this->getTemplateParams($config, 'clsms', $type, $params);
        $access_key_id = $config['access_key_id'] ?: '';
        $access_key_secret = $config['access_key_secret'] ?: '';
        $sign_name = $config['sign_name'] ?: '';
        $template_message = $template['template'];
        foreach($params as $key=>$value){
            $template_message = str_replace('${' . $key . '}', $params[$key], $template_message);
        }
        if (empty($access_key_id) || empty($access_key_secret)) {
            throw new \Exception("暂未配置({$config['sms_type']})短信的授权、短信签名等参数", $config['exception_code']);
        }
        try {
            $url = 'http://intapi.253.com/send/json';
            //创蓝接口参数
            $postArr = array(
                'account'  => $access_key_id,
                'password' => $access_key_secret,
                'msg'      => '【' . $sign_name . '】' . $template_message,
                'mobile'   => "0086" . $mobile
            );
            $postFields = json_encode($postArr);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'   //json版本需要填写  Content-Type: application/json;
            ));
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //若果报错 name lookup timed out 报错时添加这一行代码
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $ret = curl_exec($ch);
            if (false == $ret) {
                $result = curl_error($ch);
                curl_close($ch);
                throw new \Exception("发送短信失败,网络错误(001)", $config['exception_code']);
            } else {
                $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (200 != $rsp) { //http code 不为200
                    $curl_error = curl_error($ch);
                    curl_close($ch);
                    throw new \Exception("发送短信失败(002)" . $rsp . "_" . $curl_error , $config['exception_code']);
                } else { //http code 为200
                    $jsonRes = json_decode($ret, 1);
                    if ($jsonRes && isset($jsonRes['code']) && $jsonRes['code'] === "0") { //发送成功
                        curl_close($ch); //关闭句柄
                        return $jsonRes['msgid'];
                    } else {
                        curl_close($ch); //关闭句柄
                        throw new \Exception("发送短信失败(003)" . $ret , $config['exception_code']);
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            throw new \Exception("发送短信失败(004) {$error}", $config['exception_code']);
        }
    }


    /**
     * 通过CURL发送HTTP请求
     * @param string $url //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     *
     */
    private function _curlPost($url, $postFields) {
        $postFields = json_encode($postFields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'   //json版本需要填写  Content-Type: application/json;
        ));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //若果报错 name lookup timed out 报错时添加这一行代码
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        if (false == $ret) {
            $result = curl_error($ch);
        } else {
            $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "请求状态 " . $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
        curl_close($ch);
        return $result;
    }
}