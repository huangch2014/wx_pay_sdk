<?php
/** 
 * 微信app支付
 * @since: 2015-11-30
 * @author: huangch2014
 */
class Wechat{
	
	var $wechat_config;
	
	function __construct($wechat_config){
		$this->wechat_config = $wechat_config;
	}
	
    /**
     * 获取微信支付签名
     * @param $order_sn
     * @param $total_fee
     */
    public function wxpaySign($order_sn = '359217132432511',$total_fee = '1') {
        $total_fee *=100;//微信已分为单位
        $prepqy_id = $this->createPrepay($order_sn,$total_fee);
        if (!$prepqy_id) return false;
        $sign_data = array(
            'appid'     => $this->wechat_config['APPID'],
            'partnerid' => $this->wechat_config['MCH_ID'],
            'prepayid'  => $prepqy_id,
            'package'   => 'Sign=WXPay',
            'noncestr'  => uniqid(),
            'timestamp' => time(),
        );
        $sign = $this->setWxSign($sign_data);
        $sign_data['sign'] = $sign;
        return $sign_data;
    }

    /**
     * 获取微信预支单
     */
    public function createPrepay($out_trade_no,$total_fee){
        $spbill_create_ip = get_client_ip();
        $nonce_str = uniqid();
        $pay_time_limit = 1800;
        $time_start = date('YmdHis',time());//交易开始时间
        $time_expire = date('YmdHis',time() + $pay_time_limit);//交易结束时间
        $sign_data = array(
            'appid'             => $this->wechat_config['APPID'],
            'mch_id'            => $this->wechat_config['MCH_ID'],
            'nonce_str'         => $nonce_str,
            'body'              => '订单的body',
            'out_trade_no'      => $out_trade_no,
            'total_fee'         => $total_fee,
            'spbill_create_ip'  => $spbill_create_ip,
            'notify_url'        => $this->wechat_config['NOTIFY_URL'],
            'trade_type'        => 'APP',
            'fee_type'          => 'CNY',
            'time_start'        => $time_start,
            'time_expire'       => $time_expire,
            'detail'            => '订单的detail',
        );
        $sign = $this->setWxSign($sign_data);
        $sign_data['sign'] = $sign;
        ksort($sign_data);
        $xml = array_to_xml($sign_data);
        $post_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response_xml = $this->https_get($post_url,$xml);
        $response_arr = $this->setXmlArray($response_xml);
        if ($response_arr['return_code'] == 'SUCCESS' && $response_arr['return_msg'] == 'OK') {
            return $response_arr['prepay_id'];
        } else {
            return false;
        }
    }

    /**
     * 生成微信支付签名
     * @param $sign_data
     */
    public function setWxSign($sign_data) {
        if (isset($sign_data['sign'])) unset($sign_data['sign']);
        ksort($sign_data);
        $sign_str = urldecode(http_build_query($sign_data));
        return strtoupper(md5($sign_str.'&key='.$this->wechat_config['KEY']));
    }

    /**
     * 从xml中获取数组
     * @return array
     */
    public function setXmlArray($xmlData) {
        if ($xmlData) {
            $postObj = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (! is_object($postObj)) {
                return false;
            }
            $array = json_decode(json_encode($postObj), true); // xml对象转数组
            return array_change_key_case($array, CASE_LOWER); // 所有键小写
        } else {
            return false;
        }
    }

    /**
     * 从xml中获取数组
     * @return array
     */
    public function getXmlArray() {
        $xmlData = file_get_contents("php://input");
        if ($xmlData) {
            $postObj = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (! is_object($postObj)) {
                return false;
            }
            $array = json_decode(json_encode($postObj), true); // xml对象转数组
            return array_change_key_case($array, CASE_LOWER); // 所有键小写
        } else {
            return false;
        }
    }

    /**
     * https请求
     * @return array
     */
    puhlic function https_get($url,$xml_data) {
        $ch = curl_init();
        $header[] = "Content-type: text/xml";//定义content-type为xml
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 验证服务器通知
     * @param $data
     * @return array|bool
     */
    public function verifyNotify() {
        $xml = $this->getXmlArray();
        if (!$xml)  return false;
        $wx_sign = $xml['sign'];
        unset($xml['sign']);
        $fb_sign = $this->setWxSign($xml);
        if ($fb_sign != $wx_sign) {
            return false;
        }
        return $xml;
    }
}
?>