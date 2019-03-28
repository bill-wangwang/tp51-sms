<?php

namespace tp51\sms\driver;

use Qcloud\Sms\SmsSingleSender;

class QcloudSms extends Base {
    public function sendSms($config, $type, $mobile, $params) {
        $template = $this->getTemplateParams($config, 'qcloudsms', $type, $params);
        $access_key_id = $config['access_key_id'] ?: '';
        $access_key_secret = $config['access_key_secret'] ?: '';
        $sign_name = $config['sign_name'] ?: '';

        if (empty($access_key_id) || empty($access_key_secret) ) {
            throw new \Exception("暂未配置({$config['sms_type']})短信的授权、短信签名等参数", $config['exception_code']);
        }
        try {
            $qcloudSms = new SmsSingleSender($access_key_id, $access_key_secret);
            $result = $qcloudSms->sendWithParam("86", $mobile, $template['template_id'], array_values($params), $sign_name, "", "");
            if (!$result) {
                throw new \Exception("发送短信失败(001)", $config['exception_code']);
            }

            $resArray = json_decode($result, 1);
            if (!is_array($resArray)) {
                throw new \Exception("发送短信失败(002)", $config['exception_code']);
            }
            if (isset($resArray['result']) && $resArray['result'] === 0) {
                $out_id = trim($resArray['sid']);
                return $out_id;
            } else {
                throw new \Exception("发送短信失败(003):" . $resArray['errmsg'] , $config['exception_code']);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            throw new \Exception("发送短信失败(004) {$error}",  $config['exception_code']);
        }
    }
}