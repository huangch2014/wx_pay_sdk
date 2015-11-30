<?php
/** 
 * 微信支付异步通知
 * @since: 2015-11-30
 * @author: huangch2014
 */
require 'wechat_config.php';
require 'wechat_api.php';
		
$wechat = new Wechat($wechat_config);
$verify_info = $wechat->verifyNotify(); // 验证通知
if ($verify_info !== false) {	
	echo 'success';
} else {
	echo 'fail';
}
?>