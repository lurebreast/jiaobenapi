<?php
/**
 常用函数类
 */
class Common
{
    const STATUS_OK = 0;
    const STATUS_FAIL = 1;

    /**
     * 用户密码加密函数
     *
     */
    public function auth_code($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 0;

        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);
        // $box = 100;

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 验证手机号是否正确
     * @author honfei
     * @param number $mobile
     */
    public function isMobile($mobile) {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
    /**
     * 生成订单号
     * $uid 用户的id
     * $type 1,提现币订单,2平台币订单不能大于9
     * 后三位id相同的用户同一毫秒下出现重复订单号概率为百分之一
     */
    public function makepayorder($uid,$type){
        $paySn = $type.date('ymd') . substr(time(), -5) . substr(microtime(), 2, 3) . sprintf('%02d', rand(0, 99)).sprintf('%03d',substr($uid, -3));
        return $paySn;
    }
    /**
     * 根据payid生成用户订单号
     *
     */
    public function makeordersn($payid,$type,$uid){
        $ordersn = $type.sprintf('%09d',$payid).sprintf('%03d',substr($uid, -3));
        return $ordersn;
    }

}
