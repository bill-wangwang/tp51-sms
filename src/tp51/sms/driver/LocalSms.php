<?php

namespace tp51\sms\driver;

use tp51\sms\Sms;

class LocalSms extends Base {
    public function sendSms($config, $type, $mobile, $params) {
        $template = $this->getTemplateParams($config,'local', $type, $params);
        return 'local_' . date('YmdHis') . '_' . rand(1000, 9999);
    }
}