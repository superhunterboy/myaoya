<?php

namespace Weiming\Libs;

use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Exception;
use Weiming\Libs\AgencyPayments\Bingo;

class Utils
{
    /**
     * 自动入款接口数据签名
     * @param  Array $dataArr  post 数据
     * @param  String $key     加密 key
     * @return String          sign 签名数据
     */
    public static function generateSignature($dataArr)
    {
        $settings = require __DIR__ . '/../../config/settings.php';
        return md5(md5("orderNo={$dataArr['orderNo']}&account={$dataArr['account']}&fee={$dataArr['fee']}&rechargeTime={$dataArr['rechargeTime']}") . $settings['key']);
    }

    /**
     * html表单去掉空格、换行符，缩进成一行
     * @param  String $str html内容
     * @return Boolean
     */
    public static function formatHtml($str)
    {
        return preg_replace("~>\\s+<~", "><", preg_replace("~>\\s+~", ">", preg_replace("~\\s+<~", "<", $str)));
    }

    /**
     * 华仁支付验签
     * @param  Array $params   数据
     * @param  String $saltKey salt key
     * @return Boolean
     */
    public static function verifySign($params, $saltKey)
    {
        $sign = $params['v_sign'];
        unset($params['v_sign']);

        $sign_str = '';
        foreach ($params as $k => $v) {
            $sign_str .= $k . $v;
        }

        return $sign === md5($sign_str . $saltKey);
    }

    /**
     * 格式化RSA秘钥
     * @param  String $key  公钥、私钥字符串
     * @param  string $type 公钥、私钥
     * @return String
     */
    public static function formatRsaKey($key, $type = 'pubKey')
    {
        $rsaKey = '';
        $step   = 64;
        for ($i = 0; $i < ceil(strlen($key) / $step); $i++) {
            $rsaKey .= substr($key, $i * $step, $step) . "\n";
        }
        if ($type == 'pubKey') {
            $rsaKey = "-----BEGIN PUBLIC KEY-----\n" . $rsaKey . "-----END PUBLIC KEY-----";
        } elseif ($type == 'priKey') {
            $rsaKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $rsaKey . "-----END RSA PRIVATE KEY-----";
        }
        return $rsaKey;
    }

    /**
     * 华仁RSA加密
     * @param $plaintext
     * @return bool|string
     */
    public static function enCrypt($plaintext, $pubKey, $isNetBank)
    {
        $split_data    = str_split($plaintext, 117);
        $encrypt_array = [];
        foreach ($split_data as $part) {
            $result = openssl_public_encrypt($part, $encrypt_string, self::formatRsaKey($pubKey, 'pubKey'));
            if (!$result) {
                return false;
            }
            $encrypt_array[] = base64_encode($encrypt_string);
        }
        if ($isNetBank) {
            // 新增URL编码
            $encrypt = [];
            foreach ($encrypt_array as $value) {
                $encrypt[] = urlencode($value);
            }
            return join('@', $encrypt);
        }
        return join('@', $encrypt_array);
    }

    /**
     * 华仁RSA解密
     * @param $ciphertext
     * @return bool|string
     */
    public static function deCrypt($ciphertext, $priKey)
    {
        $split_array    = explode('@', $ciphertext);
        $decrypt_string = '';
        foreach ($split_array as $part) {
            $result = openssl_private_decrypt(base64_decode($part), $decrypt_data, self::formatRsaKey($priKey, 'priKey'));
            if (!$result) {
                return false;
            }
            $decrypt_string .= $decrypt_data;
        }
        return $decrypt_string;
    }

    /**
     * 天付宝 RSA加密
     * @param $plaintext
     * @return bool|string
     */
    public static function tfbEnCrypt($plaintext, $pubKey)
    {
        $split_data    = str_split($plaintext, 117);
        $encrypt_array = [];
        foreach ($split_data as $part) {
            $result = openssl_public_encrypt($part, $encrypt_string, self::formatRsaKey($pubKey, 'pubKey'));
            if (!$result) {
                return false;
            }
            $encrypt_array[] = $encrypt_string;
        }
        return base64_encode(join('', $encrypt_array));
    }

    /**
     * 天付宝 RSA解密
     * @param $ciphertext
     * @return bool|string
     */
    public static function tfbDeCrypt($ciphertext, $priKey)
    {
        $split_array    = str_split(base64_decode($ciphertext), 128);
        $decrypt_string = '';
        foreach ($split_array as $part) {
            $result = openssl_private_decrypt($part, $decrypt_data, self::formatRsaKey($priKey, 'priKey'));
            if (!$result) {
                return false;
            }
            $decrypt_string .= $decrypt_data;
        }
        return $decrypt_string;
    }

    /**
     * 天付宝 RSA解密
     * @param $ciphertext
     * @return bool|string
     */
    public static function tfbDeCryptNoFormat($ciphertext, $priKey)
    {
        $split_array    = str_split(base64_decode($ciphertext), 128);
        $decrypt_string = '';
        foreach ($split_array as $part) {
            $result = openssl_private_decrypt($part, $decrypt_data, $priKey);
            if (!$result) {
                return false;
            }
            $decrypt_string .= $decrypt_data;
        }
        return $decrypt_string;
    }

    /**
     * 天付宝 网银 验证签名
     * @param  string  $data
     * @param  string  $pubKey
     * @param  string  $sign
     * @return boolean
     */
    public static function isTianfubaoRsaVerifySign($data, $pubKey, $sign)
    {
        $rsaKey = '';
        $step   = 64;
        for ($i = 0; $i < ceil(strlen($pubKey) / $step); $i++) {
            $rsaKey .= substr($pubKey, $i * $step, $step) . "\n";
        }
        $rsaKey = "-----BEGIN PUBLIC KEY-----\n" . $rsaKey . "-----END PUBLIC KEY-----";
        if (openssl_public_decrypt(base64_decode($sign), $result, $rsaKey)) {
            return sha1($data, true) == $result;
        }
        return false;
    }

    /**
     * 天付宝 网银 验证签名 数据拼接
     * @param  array  $arr
     * @return boolean
     */
    public static function isTianfubaoVerifyStr($arr)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "retcode" != $k && "retmsg" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return substr($signPars, 0, strlen($signPars) - 1);
    }

    /**
     * 天付宝 微信、QQ钱包、支付宝 验证签名
     * @param  array  $arr
     * @param  string  $md5key
     * @return boolean
     */
    public static function isTianfubaoVeritfySign($arr, $md5key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "retcode" != $k && "retmsg" != $k && "sign_type" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return strtolower($arr['sign']) == strtolower(md5($signPars . 'key=' . $md5key));
    }

    /**
     * 验证是否是json数据
     * @param  String  $str json字符串
     * @return boolean
     */
    public static function isJSON($str)
    {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 沃雷特支付生成支付二维码
     * @param  String $data
     * @return String
     */
    public static function getQrcode($data)
    {
        $fileDir    = __DIR__ . '/../../public/qrcode/';
        $qrFileName = date('YmdHis') . mt_rand(time(), time() + rand(10000, 99999));
        $renderer   = new Png();
        $renderer->setMargin(1);
        $renderer->setHeight(256);
        $renderer->setWidth(256);
        $writer = new Writer($renderer);
        $writer->writeFile($data, $fileDir . $qrFileName . '.png');
        if (is_file($fileDir . $qrFileName . '.png')) {
            return 'http://' . $_SERVER['HTTP_HOST'] . '/qrcode/' . $qrFileName . '.png';
        }
        return '';
    }

    /**
     * RSA签名
     * @param  String $data   待签名数据
     * @param  String $priKey 私钥
     * @return String
     */
    public static function rsaSign_string($data, $priKey, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $rsaKey = '';
        $step   = 64;
        for ($i = 0; $i < ceil(strlen($priKey) / $step); $i++) {
            $rsaKey .= substr($priKey, $i * $step, $step) . "\n";
        }
        $rsaKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $rsaKey . "-----END RSA PRIVATE KEY-----";
        $res    = openssl_get_privatekey($rsaKey);
        openssl_sign($data, $sign, $res, $signature_alg);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * RSA验签
     * @param String $data 待签名数据，字符串
     * @param String $pubKey 公钥字符串
     * @param String $sign 要校对的签名结果
     * @return Boolean 验证结果
     */
    public static function rsaVerify_string($data, $pubKey, $sign, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $rsaKey = '';
        $step   = 64;
        for ($i = 0; $i < ceil(strlen($pubKey) / $step); $i++) {
            $rsaKey .= substr($pubKey, $i * $step, $step) . "\n";
        }
        $rsaKey = "-----BEGIN PUBLIC KEY-----\n" . $rsaKey . "-----END PUBLIC KEY-----";
        $res    = openssl_get_publickey($rsaKey);
        $result = (bool) openssl_verify($data, base64_decode($sign), $res, $signature_alg);
        openssl_free_key($res);
        return $result;
    }

    /**
     * 对收到的回调通知验签
     * @param  String $pubKey  公钥
     * @param  Array $dataArr 验签数据
     * @return Boolean
     */
    public static function validateRsaSign($pubKey, $dataArr)
    {
        //公钥KEY，待验证数组
        $strData   = '';
        $validSign = $dataArr['sign']; //回调通知中的签名
        //删掉回调通知中的签名字段，准备验证处理
        unset($dataArr['sign']);
        //unset($dataArr['signMethod']);
        ksort($dataArr);
        foreach ($dataArr as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $strData .= $k . '=' . $v . '&';
        }
        $strData = substr($strData, 0, -1);
        return self::rsaVerify_string($strData, $pubKey, $validSign);
    }

    /**
     * 金海哲支付微信、支付宝返回二维码数据验签
     * @param  String $pubKey
     * @param  String $jsonStr
     * @return Boolean
     */
    public static function verifyRsaSign($pubKey, $jsonStr)
    {
        $dataArr    = json_decode($jsonStr, true);
        $verifySign = $dataArr['sign'] ?? '';
        unset($dataArr['sign']);
        return self::rsaVerify_string(json_encode($dataArr, JSON_UNESCAPED_SLASHES), $pubKey, $verifySign);
    }

    /**
     * 金海哲支付微信、支付宝、网银回调数据验签
     * @param  String $pubKey
     * @param  String $dataArr
     * @return Boolean
     */
    public static function verifyRsaSignForCallback($pubKey, $dataArr)
    {
        $verifySign = $dataArr['sign'];
        $strData    = $dataArr['ret'] . '|' . $dataArr['msg'];
        return self::rsaVerify_string($strData, $pubKey, $verifySign);
    }

    /**
     * 自由付签名验证
     * @param  array  $arr 自由付返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    public static function isZiyoufuSign($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign        = strtolower(md5($signPars));
        $souceSign   = $arr['sign'] ?? '';
        $ziyoufuSign = strtolower($souceSign);
        return $sign == $ziyoufuSign;
    }

    /**
     * 天付宝签名验证
     * @param  array  $arr 天付宝返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    public static function isTianfubaoSign($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "retcode" != $k && "retmsg" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign          = strtolower(md5($signPars));
        $souceSign     = $arr['sign'] ?? '';
        $tianfubaoSign = strtolower($souceSign);
        return $sign == $tianfubaoSign;
    }

    /**
     * 获取xml编码
     * @param  string $xml xml字符串
     * @return string      编码
     */
    public static function getXmlEncode($xml)
    {
        $ret = preg_match("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if ($ret) {
            return strtoupper($arr[1]);
        } else {
            return "";
        }
    }

    /**
     * xml转array
     * @param  string $xml
     * @return array
     */
    public static function toArray($xml, $node = 2)
    {
        $tmpArr = [];
        $xml    = simplexml_load_string($xml);
        $encode = self::getXmlEncode($xml);
        //我付支付
        if ($node > 2) {
            $xml = $xml->response;
        }

        if ($xml && $xml->children()) {
            foreach ($xml->children() as $node) {
                //有子节点
                if ($node->children()) {
                    $k       = $node->getName();
                    $nodeXml = $node->asXML();
                    $v       = substr($nodeXml, strlen($k) + 2, strlen($nodeXml) - 2 * strlen($k) - 5);
                } else {
                    $k = $node->getName();
                    $v = (string) $node;
                }
                if ($encode != "" && $encode != "UTF-8") {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $tmpArr[$k] = $v;
            }
        }
        return $tmpArr;
    }

    /**
     * array转xml
     * @param  array $array
     * @return string
     */
    public static function toXml($array)
    {
        $xml = '<xml>';
        foreach ($array as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * array转xml
     * @param  array $array
     * @return string
     */
    public static function arrToXml($array)
    {
        $xml = '<?xml version="1.0" encoding="GBK"?>' . PHP_EOL . '<data>' . PHP_EOL;
        foreach ($array as $k => $v) {
            if (!empty($v)) {
                $xml .= "\t" . '<' . $k . '>' . mb_convert_encoding($v, 'gbk', 'utf-8') . '</' . $k . '>' . PHP_EOL;
            } else {
                $xml .= "\t" . '<' . $k . '/>' . PHP_EOL;
            }
        }
        $xml .= '</data>';
        return $xml;
    }

    /**
     * 将xml转换为数组，考虑到xml文档中可能会包含<![CDATA[]]>标签，第三个参数设置为LIBXML_NOCDATA
     * @param string $xml:xml文件或字符串
     * @return array
     */
    public static function xmlToArr($xml)
    {
        if (file_exists($xml)) {
            libxml_disable_entity_loader(false);
            $xml_string = simplexml_load_file($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        } else {
            libxml_disable_entity_loader(true);
            $xml_string = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return json_decode(json_encode($xml_string), true);
    }

    /**
     * 自由付获取签名字符串
     * @param  array $params
     * @return string
     */
    public static function getSignStr($params)
    {
        $signPars = "";
        ksort($params);
        foreach ($params as $k => $v) {
            if ("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return $signPars;
    }

    /**
     * 发送post请求
     * @param  string $url
     * @param  string $data
     * @return string|boolean
     */
    public static function sendPost($url, $data, $time = '', $debug = '')
    {
        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        if ($time !== '') {
            curl_setopt($ch, CURLOPT_TIMEOUT, $time);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        //要传送的所有数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // 执行操作
        $res     = curl_exec($ch);
        $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($res == null || $resCode != "200") {
            if (empty($debug)) {
                curl_close($ch);
                return false;
            } else {
                $res = curl_error($ch);
            }

        }
        curl_close($ch);
        return $res;
    }

    /**
     * 发送多种格式post请求
     * @param  string $url
     * @param  string $data
     * @return string|boolean
     */
    public static function sendPostMore($url, $data, $type = 'json', $time = '', $debug = '')
    {
        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        if ($time !== '') {
            curl_setopt($ch, CURLOPT_TIMEOUT, $time);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        }
        if ($type = 'json') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($data)]
            );
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        //要传送的所有数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // 执行操作
        $res     = curl_exec($ch);
        $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($res == null || $resCode != "200") {
            if (empty($debug)) {
                curl_close($ch);
                return false;
            } else {
                $res = curl_error($ch);
            }

        }
        curl_close($ch);
        return $res;
    }

    /**
     * 获取客户端ip
     * @return string
     */
    public static function getIp()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if ($_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'unknown';
        }

        return $ip;
    }

    /**
     * 拼接sql语句作为查询条件
     * @param  String $ids 业务平台id，以逗号分隔
     * @return String
     */
    public static function getRawSql($ids)
    {
        $sql    = '';
        $idsArr = explode(',', $ids);
        foreach ($idsArr as $key => $val) {
            $sql .= " OR FIND_IN_SET('{$val}', `company_ids`)";
        }
        return $sql;
    }

    /**
     * 返回需要删除的id
     * @param  String $ids 以/分隔的id
     * @return Array or String
     */
    public static function getIds($ids)
    {
        $tmpArr = [];
        $idsArr = explode('/', $ids);
        foreach ($idsArr as $key => $val) {
            if ($val && preg_match('/^[0-9]*$/', $val)) {
                array_push($tmpArr, $val);
            }
        }
        if ($tmpArr) {
            return $tmpArr;
        }
        return '';
    }

    /**
     * 根据下单时间获取单号
     * @param  String $currentTime 下单时间
     * @return String
     */
    public static function getOrderId($currentTime)
    {
        return str_replace(['-', ' ', ':'], ['', '', ''], $currentTime) . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
    }

    /**
     * 乐盈支付RSA签名，目前没用到RSA，用的是MD5
     * @param String $javaClass java类
     * @param String $signMsg   验签数据
     * @param String $charset   编码
     */
    public static function LAJPCall($javaClass, $signMsg, $charset)
    {
        if (!is_string($javaClass)) {

            throw new Exception("[LAJP Error] lajp_call function's first argument must be string \"class_name::method_name\".", 101);
        }

        if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false) {

            throw new Exception("[LAJP Error] socket create error.", 102);
        }

        if (socket_connect($socket, '127.0.0.1', 21230) === false) {

            throw new Exception("[LAJP Error] socket connect error.", 102);
        }

        // 消息体序列化
        $request = serialize([$javaClass, $signMsg, $charset]);
        $req_len = strlen($request);

        $request = $req_len . "," . $request;

        // echo $request;

        // 发送
        $send_len = 0;
        do {

            if (($sends = socket_write($socket, $request, strlen($request))) === false) {

                throw new Exception("[LAJP Error] socket write error.", 102);
            }

            $send_len += $sends;

            $request = substr($request, $sends);

        } while ($send_len < $req_len);

        // 接收
        $response = "";
        while (true) {

            $recv = "";
            if (($recv = socket_read($socket, 1400)) === false) {

                throw new Exception("[LAJP Error] socket read error.", SOCKET_ERROR);
            }

            if ($recv == "") {

                break;
            }

            $response .= $recv;
            // echo $response";
        }

        // 关闭
        socket_close($socket);

        $rsp_stat = substr($response, 0, 1); //返回类型 "S":成功 "F":异常
        $rsp_msg  = substr($response, 1); //返回信息

        // echo "返回类型:{$rsp_stat},返回信息:{$rsp_msg}<br>";

        if ($rsp_stat == "F") {

            // 异常信息不用反序列化
            throw new Exception("[LAJP Error] Receive Java exception: " . $rsp_msg, 104);

        } else {

            // 返回非void
            if ($rsp_msg != "N") {
                // 反序列化
                return unserialize($rsp_msg);
            }
        }
    }

    /**
     * 多多验证签名，默认是MD5
     * @param array  $data   验签数据
     * @param string $duoDuoKey    商户key
     */
    public static function verifySignDuoduo($data, $duoDuoKey)
    {

        $info = $data['SignInfo'];

        unset($data['SignInfo']);

        $stream = http_build_query($data);

        $singInfo = md5(md5($stream) . $duoDuoKey);

        if ($singInfo === $info) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 仁信验证签名，默认是MD5
     * @param array  $data   验签数据
     * @param string $duoDuoKey    商户key
     */
    public static function verifySignRenxin($data, $renxinKey)
    {

        $sign = $data['sign'];

        unset($data['sign']);
        unset($data['sysnumber']);
        unset($data['attach']);

        $stream = http_build_query($data);

        $singInfo = md5($stream . $renxinKey);

        if ($singInfo === $sign) {
            return true;
        }
        return false;
    }

    /**
     * 高通支付签名
     * @param array $array  签名数据
     * @param string $md5key
     */
    public static function gaotongSign($array, $md5key)
    {
        $url = '';
        foreach ($array as $key => $v) {
            if ($key !== 'hrefbackurl' and $key !== 'attach' && $key !== 'sign') {
                $url .= $key . '=' . $v . '&';
            }
        }
        return md5(substr($url, 0, strlen($url) - 1) . $md5key);
    }

    /**
     * 天付寶，得到待签名字符串
     * @param  array  $arr 签名数据
     * @return string
     */
    public static function getSignatureString($arr, $hasign = false)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ($hasign === false) {
                if ("sign" != $k && "" != $v) {
                    $signPars .= $k . "=" . $v . "&";
                }
            } else {
                if ("" != $v) {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
        }
        return $signPars;
    }

    /**
     * 签名验证
     * @param  array  $arr 接口返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    public static function verifySignature($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "" !== $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = strtolower(md5($signPars));
        return $sign == strtolower($arr['sign']);
    }

    /**
     * RSA加密
     * @param $plaintext
     * @return bool|string
     */
    public static function enCryptByRSA($plaintext, $pubKey)
    {
        $split_data    = str_split($plaintext, 117);
        $encrypt_array = [];
        foreach ($split_data as $part) {
            $result = openssl_public_encrypt($part, $encrypt_string, self::formatRsaKey($pubKey, 'pubKey'));
            if (!$result) {
                return false;
            }
            $encrypt_array[] = $encrypt_string;
        }
        return base64_encode(join('', $encrypt_array));
    }

    /**
     * RSA解密
     * @param $ciphertext
     * @return bool|string
     */
    public static function deCryptByRSA($ciphertext, $priKey)
    {
        $split_array    = str_split(base64_decode($ciphertext), 128);
        $decrypt_string = '';
        foreach ($split_array as $part) {
            $result = openssl_private_decrypt($part, $decrypt_data, self::formatRsaKey($priKey, 'priKey'));
            if (!$result) {
                return false;
            }
            $decrypt_string .= $decrypt_data;
        }
        return $decrypt_string;
    }

    /**
     * 新雅付签名验证
     * @param  array  $arr 新雅付返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    public static function isYafuNew($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && $k != 'merRemark' && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign     = strtolower(md5($signPars));
        $yafuSign = strtolower($arr['sign']);
        return $sign == $yafuSign;
    }

    /**
     * 解析 base64 格式的图片
     * @param  String $base64 base64 格式的图片
     * @return Array 图片信息
     */
    public static function parseBase64Image($base64)
    {
        if (strpos($base64, 'base64') === false) {
            return null;
        }
        $mime    = substr($base64, 5, strpos($base64, 'base64') - 6);
        $content = substr($base64, strpos($base64, 'base64') + 7);
        $ext     = '';
        if ($mime == 'image/jpeg') {
            $ext = 'jpg';
        } elseif ($mime == 'image/png') {
            $ext = 'png';
        } elseif ($mime == 'image/gif') {
            $ext = 'gif';
        } elseif ($mime == 'image/bmp') {
            $ext = 'bmp';
        }
        if (!in_array($ext, ['jpg', 'png', 'gif', 'bmp'])) {
            return null;
        }
        return [
            'mime'    => $mime,
            'content' => $content,
            'ext'     => $ext,
        ];
    }

    /**
     * 时间格式化
     * @param string $dateformat 时间格式
     * @param int $timestamp 时间戳
     * @param int $timeoffset 时区偏差
     * @return string
     */
    public static function myGMDate($dateformat = 'Y-m-d H:i:s', $timestamp = '', $timeoffset = 8)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        return gmdate($dateformat, $timestamp + $timeoffset * 3600);
    }

    /**
     * 关联按照键值英文顺序排序
     * @param Array $data  需要排序数组
     * @param String $sort 顺序
     * @return Array 排序后的数组
     */
    public static function mySort($data, $sort = 'asc')
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            //删除值为空的
            if (empty($value)) {
                unset($data[$key]);
            } else {
                $keys[] = $key;
            }
        }
        unset($value);
        if ($sort == 'asc') {
            sort($keys);
        } else {
            asort($keys);
        }
        foreach ($keys as $value) {
            foreach ($data as $key => $value1) {
                if ($key == $value) {
                    $returnData[$key] = $value1;
                }
            }
        }
        return $returnData;
    }

    /**
     * 递归xml转数组
     * @param Xml $xml  需要转数组的xml字符串
     * @return Array 成功后的数组或者失败返回原字符串
     */
    public static function xmlToArray($xml)
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            $arr   = [];
            for ($i = 0; $i < $count; $i++) {
                $key = $matches[1][$i];
                $val = self::xmlToArray($matches[2][$i]); // 递归
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if (!array_key_exists(0, $arr[$key])) {
                            $arr[$key] = [
                                $arr[$key],
                            ];
                        }
                    } else {
                        $arr[$key] = [
                            $arr[$key],
                        ];
                    }
                    $arr[$key][] = $val;
                } else {
                    $arr[$key] = $val;
                }
            }
            return $arr;
        } else {
            return $xml;
        }
    }

    public static function privateSign($str, $privateFile, $password)
    {
        $str            = md5($str);
        $str            = pack("H*", $str);
        $pkcs12certdata = file_get_contents($privateFile);
        openssl_pkcs12_read($pkcs12certdata, $certs, $password);
        $priv_key_id = openssl_get_privatekey($certs['pkey']);
        openssl_sign($str, $crypted, $priv_key_id, OPENSSL_ALGO_SHA1);
        $crypted = base64_encode($crypted);
        return $crypted;
    }

    public static function publicVerify($str, $publicFile, $crypted)
    {
        $str              = md5($str);
        $str              = pack("H*", $str); //转成16进制的字符串
        $binary_signature = base64_decode($crypted); //加密
        $pkcs12certdata   = file_get_contents($publicFile); //以字符串的形式获取文件中的信息
        $public_key       = openssl_get_publickey($pkcs12certdata); //获取公钥
        $ok               = openssl_verify($str, $binary_signature, $public_key, OPENSSL_ALGO_SHA1); //1.数据，2.签名，3公钥，4
        return $ok;
    }

    /**
     * 喜付签名字符串组合
     * @param  array  $params 请求参数
     * @return string
     */
    public static function getXifuSignStr($params = [])
    {
        $signPars = "";
        ksort($params);
        foreach ($params as $k => $v) {
            if ("" != $v && "sign" != $k && "signType" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return rtrim($signPars, '&');
    }

    /**
     * 获取毫秒时间戳
     * @return string
     */
    public static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 加密方法
     */
    public static function encryptAES($data, $key)
    {
        return bin2hex(openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA));
    }

    /**
     * 解密方法
     */
    public static function decryptAES($str, $key)
    {
        return openssl_decrypt(hex2bin($str), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    public static function getSignString($params = [], $md5Key = '')
    {
        return ksort($params) ? strtoupper(md5(json_encode($params, 320) . $md5Key)) : '';
    }

    /**
     * des-ecb加密
     * @param string  $data 要被加密的数据
     * @param string  $key 加密密钥
     */
    public static function des_ecb_encrypt($data, $key)
    {
        return openssl_encrypt($data, 'des-ecb', $key);
    }

    /**
     * des-ecb解密
     * @param string  $data 加密数据
     * @param string  $key 加密密钥
     */
    public static function des_ecb_decrypt($data, $key)
    {
        return openssl_decrypt($data, 'des-ecb', $key);
    }

    /**
     * Bingo第三方接口在代付单下发前要先查询余额
     * @param  Array $conf   商户号、Key信息
     * @param  Integer $amount 代付金额，单位分
     * @return Array [账户ID, D0=0 OR T1=1]
     */
    public static function getBingoCanUseBalance($conf, $amount)
    {
        $balanceArr = Bingo::getInstance($conf)->generateSignature([
            'orderNo' => self::getOrderId(date('YmdHis')), // 仅仅作为 requestId 使用
        ], 'balanceQuery')->sendRequest();
        // 余额查询
        // Array
        // (
        //     [key] => 00
        //     [msg] => 账户查询成功
        //     [requestId] => 201806261433086475780
        //     [respCode] => 00
        //     [respMsg] => 通讯成功
        //     [result] => {"accountinfo":[{"d0frozen":201,"t1can_use":0,"d0balance":4980,"t1balance":0,"accountcode":958,"d0can_use":4779,"account_name":"SYX（雨157）4081","t1frozen":0}],"merno":"312018042223067147","method":"payment.loadBanlance","skipUpmer":[]}
        //     [status] => 1
        // )
        if ($balanceArr && isset($balanceArr['respCode']) && $balanceArr['respCode'] == '00') {
            $accountinfo = $balanceArr['accountinfo'] ?? null;
            // Array
            // (
            //     [0] => Array
            //         (
            //             [d0frozen] => 402
            //             [t1can_use] => 0
            //             [d0balance] => 4980
            //             [t1balance] => 0
            //             [accountcode] => 958
            //             [d0can_use] => 4578
            //             [account_name] => SYX（雨157）4081
            //             [t1frozen] => 0
            //         )
            // )
            if ($accountinfo && is_array($accountinfo)) {
                // 多个账户
                $tmpArr = [$accountinfo[0]['accountcode'], 0]; // 万一没有余额了就走 D0
                foreach ($accountinfo as $account) {
                    $d0Balance   = $account['d0can_use'] ?? 0;
                    $t1Balance   = $account['t1can_use'] ?? 0;
                    $accountcode = $account['accountcode'] ?? 0;
                    if ($d0Balance >= $amount) {
                        $tmpArr = [$accountcode, 0];
                        break;
                    } elseif ($t1Balance >= $amount) {
                        $tmpArr = [$accountcode, 1];
                        break;
                    }
                }
                return $tmpArr;
            }
        }
    }

    public static function HmacMd5($data, $key)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)
        //需要配置环境支持iconv，否则中文参数不能正常处理
        $key  = iconv("GBK", "UTF-8", $key);
        $data = iconv("GBK", "UTF-8", $data);
        $b    = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key    = str_pad($key, $b, chr(0x00));
        $ipad   = str_pad('', $b, chr(0x36));
        $opad   = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }
}
