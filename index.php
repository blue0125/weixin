<?php
/**
  * wechat index php
  */

define("ROOT", dirname(__FILE__));

require_once ROOT . '/config/config.php';
if ($config['debug']) {
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    //product
    error_reporting(0);
}

function __autoload ($className) {
    $classFile = ROOT . '/class/' . $className . '.php';
    if (file_exists( $classFile)) {
        require_once($classFile);
    }
}

//��ַ����
$wechatObj = new WechatCallbackapi();
$wechatObj->valid();

$content = $wechatObj->getMsg();

if ($content['MsgType'] == 'text') {
    $wechatObj->responseMsg($content);
} else {
    $wechatObj->responseMsg('�ף����ܻ�ûʵ��Ŷ��');
}

?>