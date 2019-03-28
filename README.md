# tp51-sms
thinkphp5.1 聚合短信类

## 安装
> composer require phpcode/tp51-sms

## 配置
- 复制`examples/config/sms.php`到项目config配置目录下（`config/sms.php`）
- 修改`.env`文件（可参考文件`examples/.env.example`和`sms.php`）
- 在对应的数据库上执行`sms_table.sql`文件的创建表语句,记得把`{$prefix}`替换为实际的`databases.php`的`prefix`对应的值
- 如果不想把短信模板配置到数据库，可以直接在`sms.php`配置文件中配置 `sms_template_list` 
## 使用
### Controller 控制器中使用
```
use tp51\sms\Sms;

$sms = new Sms();
$type = 'register'; //短息模板类型标识 如 register | find_password
$mobile = '13800138000'; //手机号
//系统内置了获取验证码方法，可以配置 验证码的长度，有效期，有效时间内获取的同一个手机号的同一类型的验证码是否一样
$code = $sms->getVerifyCode($type, $mobile);
//发送短信
$out_id = $sms->send($type, $mobile, ['code'=>$code]);

------------------------------------------------------------------------------------------
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
```

## 支持
- 阿里云的阿里大鱼
- 腾讯云的qcloudsms
- 本地发送（不真实发送短信，配合万能验证码在开发环境调试）

## 重要选项
- `sms_type` 支持[ `dysms` 、 `qcloudsms` 、 `local` ]
- `sms_type`设置为`local`时，配合`public_verify_code`可以配置万能验证码以节约短信费用
- 短信模板除了腾讯云的需要`{1}`, `{2}`这种格式外，其它的都是 `${code}`， `${name}`的形式，因此为了更好的兼容各种短信，建议程序使用可以key的参数 `['code'=>$code, 'name'=>$name]` 而不是`[$code, $name]`

## 功能
- 灵活的配置（可以参考`Sms.php`的配置项`$_config`）
- 支持自定义验证码长度(`verify_code_length`)或`->setVerifyCodeLength()`
- 支持配置有效时间内获取同种类型的验证码是否变化(`same_as_last_time`)
- 支持自定义允许尝试验证码最大次数(`max_try`)或`->setMaxTry()`
- 支持验证成功后是否立即销毁验证码(`delete`)或`->setDelete()`
- 支持设置发送短信成功后的回调`->setSendCallBack()`
- 支持非数据库设置短信模板（优先级高于数据库配置短信模板）`->setSmsTemplateList()`

## 注意事项
- 生产环境建议把 `use_cache`设置为`true`以提高性能
- 如果更改了短信模板的并且使用缓存`use_cache`为`true`时，需要手动调用`clearTemplatesCache()`方法清除短信模板，否则不生效