<?php

namespace tp51\sms\driver;

use think\Db;

class Base {
    protected function getTemplates($config, $sms_type) {
        if(isset($config['sms_template_list']) && is_array($config['sms_template_list']) && sizeof($config['sms_template_list'])>0 ){
            return $config['sms_template_list'];
        }
        if(isset($config['table_sms_template']) && $config['table_sms_template']){
            $cache_key = $config['cache_key_prefix_sms_template'] . $sms_type;
            if (!($templates = cache($cache_key))) {
                $templates = [];
                $data = Db::table(config('database.prefix') . $config['table_sms_template'])->where('sms_type', '=', $sms_type)->column('title,template_id,params,template', 'type');
                foreach ($data as $key => $value) {
                    $params = $value['params'] ? array_map('trim', explode(',', $value['params'])) : [];
                    $value['params'] = $params;
                    $templates[$key] = $value;
                }
                //为了效率，缓存起来
                if ($config['use_cache']) {
                    cache($cache_key, $templates);
                }
            }
        } else {
            $templates = [];
        }
        return $templates;
    }

    protected function getTemplateParams($config, $sms_type, $type, $params = []) {
        $templates = $this->getTemplates($config, $sms_type);
        $template = isset($templates[$type]) ? $templates[$type] : null;
        if (!$template) {
            throw new \Exception("无效的短信类型{$sms_type}-{$type}", $config['exception_code']);
        }
        if ($template['params']) {
            if (sizeof($template['params']) != sizeof($params)) {
                throw new \Exception("短信参数个数不正确", $config['exception_code']);
            }
            //如果不是腾讯云还需判断对应的key是否存在并且不能为空
            //实际上创蓝短信无需模板，这里为了统一必须先建立模板
            if ( !in_array($sms_type, ['qcloudsms'])   ) {
                foreach ($template['params'] as $key => $value) {
                    if (!isset($params[$value]) || $params[$value] === '') {
                        throw new \Exception("短信类型{$sms_type}-{$type}缺少必填参数{$value}或参数{$value}为空", $config['exception_code']);
                    }
                }
            }
        }
        return $template;
    }
}