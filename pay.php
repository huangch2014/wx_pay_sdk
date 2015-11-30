<?php
/** 
 * 微信支付订单
 * @since: 2015-11-30
 * @author: huangch2014
 */
require 'wechat_config.php';
require 'wechat_api.php';

$total_fee = 1.00; //金额	
$params['total_fee'] = $total_fee*100;
$params['body']='订单body';

$wechat = new Wechat($wechat_config);
//提交订单
$tran_result = $wechat->createPrepay($params['out_trade_no'],$params['total_fee']);
?>
