<?php

namespace tp51\sms\driver;

use tp51\sms\sdk\alidayu\ALiDySms;

class DySms extends Base {
    public function sendSms($config, $type, $mobile, $params) {
        $template = $this->getTemplateParams($config, 'dysms', $type, $params);
        $access_key_id = $config['access_key_id'] ?: '';
        $access_key_secret = $config['access_key_secret'] ?: '';
        $sign_name = $config['sign_name'] ?: '';
        $domain = $config['domain'] ?: '';
        $region_id = $config['region_id'] ?: '';
        $action = $config['action'] ?: '';
        $version = $config['version'] ?: '';
        if (empty($access_key_id) || empty($access_key_secret) || empty($sign_name) || empty($domain) || empty($region_id) || empty($action) || empty($version)) {
            throw new \Exception("暂未配置({$config['sms_type']})短信的授权、短信签名等参数");
        }
        $dySms = new ALiDySms();
        $requestParam = [
            'PhoneNumbers'  => $mobile,
            'SignName'      => $sign_name,
            'TemplateCode'  => $template['template_id'],
            'TemplateParam' => $params,
            'RegionId'      => $region_id,
            'Action'        => $action,
            'Version'       => $version,
        ];
        if (!empty($requestParam["TemplateParam"]) && is_array($requestParam["TemplateParam"])) {
            $requestParam["TemplateParam"] = json_encode($requestParam["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        $content = $dySms->request($access_key_id, $access_key_secret, $domain, $requestParam);
        if (is_object($content) && isset($content->Message) && isset($content->BizId) && $content->Message == 'OK') {
            $out_id = $content->BizId;
            return $out_id;
        } else {
            $error = $content->Message ?: '';
            throw new \Exception("发送短信失败 {$error}", $config['exception_code']);
        }
    }
}