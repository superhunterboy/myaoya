<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class PingAn
{
    //网关
    private $getOpenIdUrl = 'https://mixpayuat4.orangebank.com.cn/org1/'; //https://api.orangebank.com.cn/org1/

    //运营机构编号
    private $open_id = 'txafCXQt058248b3230c9081ff90ce80';

    //运营机构公钥
    private $open_key ='aG0ck19g2HdthGRdSCfmiloOoGXoOzWZ';

    //商户编号
    private $shop_no = '880000867';//

    //商户公钥
    private $public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuyohp+CrDTaz6tW2uMrW
xs2lFwEbRCsPg69gObtjirgMuGtcB+vhzCFn6rN8IvohvpGkO8vjZw6qrmlbNrau
aUuW5j82eyZ749ZfTggFjIqteL9MeF88sAY3rw0AItcUUP3XaYamedKGrdeellfb
gvyaJJ+m2evY/yP9FRTVEKgmbi0UvNMvCqqiWty3H3WURFjjj2J+/4b5zI8od4rS
TsHetD8psOViMbHuYoqhl583UW/6veu1+ZvKPkJ8ZneP/jGCWe3BNWUMH3Y9+rPS
On+pnWmV3R926qKReeZBdMfK9ExYMFZi7ZfypEASz5pG+Jh5lTAwvgEATznQ7iES
NQIDAQAB
-----END PUBLIC KEY-----';

    //商户私钥
    private $private_key = '-----BEGIN RSA PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7KiGn4KsNNrPq
1ba4ytbGzaUXARtEKw+Dr2A5u2OKuAy4a1wH6+HMIWfqs3wi+iG+kaQ7y+NnDqqu
aVs2tq5pS5bmPzZ7Jnvj1l9OCAWMiq14v0x4XzywBjevDQAi1xRQ/ddphqZ50oat
156WV9uC/Jokn6bZ69j/I/0VFNUQqCZuLRS80y8KqqJa3LcfdZREWOOPYn7/hvnM
jyh3itJOwd60Pymw5WIxse5iiqGXnzdRb/q967X5m8o+Qnxmd4/+MYJZ7cE1ZQwf
dj36s9I6f6mdaZXdH3bqopF55kF0x8r0TFgwVmLtl/KkQBLPmkb4mHmVMDC+AQBP
OdDuIRI1AgMBAAECggEAYH/ihmoCB3gS35t88F40yp+w5OF/K3CAOUcs99c0BYvL
kzJXawYlj1gR+iK8eFQ7HcR9xw7imnonanGLw/QAjO2aSfCBqJE5A0m+Lb9ZDRNb
SYxoYw/HfIJYJ3sufMgkA3Y9oHz6TIlNQ0iOzblSxoBzATsHjghaA4KMtFffWwxV
geY1ozHGwt4pOv5opdr7JfJHz4Xv/G2wbmDpYEwlHWn4Iu1QZgOcmnufzIjEK7OX
SvfEc0Ohjta4TikpfJK9W4gZ28B3yPSUmNlszsRUbOT8R5sI2QIYHemlYuyY9h9d
1ZxgMv6QVu2dYdZSGHOzqd04mueYzPIWRD4WRETvaQKBgQDk6xwaq0JnZBVy9UXb
S9Kn75SaYferqgKClYbeu7GfzYyjj5N/XZEuj5DwfxQa2jijy7QqX7Tqskridh7+
sTcNDZZ4J6dT5Tivgfb3YQCVXIWL16uqSdasG1thGOAI9/0tzkHjknpjIpMNcT3s
15I7tx7b4kyUlhR1FTKHygh5IwKBgQDRTn1V0iwbi3P0hV3bqHRxcCLmO9Bhdf3U
ekDiVvegtihLVZoUGCx/aOlhWQa5+1SVMziB7ms+16vEB9QI0wZXhrHCBszknMvY
JFp9AuErvFAVCkgL0cAnhvj6QBFji5AFElNyNlExZ+hUpj6nBB/kmvF/kyiOQdAQ
M6WInJv4xwKBgC+aJFH3YuuVCFDLaCdd8QQy8bfYv2PXCoaVCWKvrRQJ7kDkzksh
7dq8x+r6wfeDgVuuNFzLYheb066b2X4k48m3FZ8Ov+DWPZ9hazWt2i00/CBETNIw
YI+RcMMUJSI4T1GDHnhwMTsEKYgWEEZ73VDFl4kp2brcKozeR4+tF235AoGBAJsk
9N5iVXNtYNwE51dkfDuBhTznZUR4s1eF7wZWtGKZ4zBEGVt/t6wRVbxkwwmkdEPL
nb0hvrjsIFPEAbUwxmimXKiXZdSnLKnf+HrlYYaLpUiTQGgSZ75k27STPNBuqKg5
t0TXYkOe46WrPJGMCx/mgc+wgsU/nzwDLFYgz7QNAoGAXNjnCWMvwDeIS4tMYDfr
VUfmvjXRFZuiOm0+a92huKlu72azi3mIme0YyIVSFGLl+Lk/BeBJYWnLNo6CsGyP
qLyi4Pkoftq+BZS3ls25pFl+8QGjrLXky+nIXbceqf1YwdNHEEIOU14jHZyPAJR2
M2Trj1iKrqJIRhkTT6WtjgE=
-----END RSA PRIVATE KEY-----';

    //请求参数
    private $get;
    private $post;

    //单一实例
    private static $instance;

    //获得实例
    public static function getInstance(array $conf){
        if (!(static::$instance instanceof static )) {
            static::$instance = new static($conf);
        }
        return static::$instance;
    }

    private function __construct($conf){
        if (count($conf) > 0) {
            $this->shop_no     = $conf['shop_no'];
        }

        if (isset($conf['public'])) {
            $this->public_key  = Utils::formatRsaKey($conf['public']);
        }
        if (isset($conf['private'])) {
            $this->private_key = Utils::formatRsaKey($conf['private'], 'priKey');
        }
        if(isset($conf['open_id'])){
            $this->open_id = $conf['open_id'];
        }
        if(isset($conf['open_key'])){
            $this->open_key = $conf['open_key'];
        }
    }

    //支付类型
    public function getPayType(){
        #无支付类型
    }

    //签名
    public function signature($tag, $page = 1){
        $this->get['open_id']   = $this->open_id;
        $this->get['lang']      ='zh-cn';
        $this->get['timestamp'] = time();
        $this->get['randStr']   = $this->myRand();
        if ($tag == '1') {
            $this->getOpenIdUrl .= 'shop/openid';
            $jsonData = json_encode(["shop_no" => $this->shop_no]);
            $this->post['data'] = $this->aes_encode($jsonData);
            $this->post['sign'] = $this->make_sign($this->get, $this->post);


        }elseif ($tag == '2') {
            $this->getOpenIdUrl .= 'merchant';
            $post['page']     = $page;
            $post['pagesize'] = 100;
            $this->post['data'] = $this->aes_encode(json_encode($post));
            $this->post['sign'] = $this->make_sign($this->get, $this->post);
        }
        return $this;
    }

    //发送
    public function payment(){
        $client = new Client();
        $get = '?' . http_build_query($this->get);                                                                                           //var_dump($this->getOpenIdUrl);exit;
        $response = $client->request('POST', $this->getOpenIdUrl . $get, ['verify' => false, 'form_params' => $this->post]);

        $state = $response->getStatusCode();
        if($state == 200){
            return $response->getBody();
        }else{
            $header = '';
            foreach ($response->getHeaders() as $name => $values) {
                $header .= $name . ': ' . implode(', ', $values) . "\r\n";
            }
            return $header;
        }
    }

    //解密获取数
    public function data_decode(string $tempStr) {
        $tempStr=hex2bin($tempStr);
        $decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$this->open_key,$tempStr,MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    //验证签名
    public function verifySign(array $params){
         if(empty($params['sign'])){
            return false;
        }
        $sign=$params['sign'];#得到返回签名字符串
        unset($params['sign']);#去掉sign节点
        ksort($params);#排序
        $arr_temp = array ();
        foreach ($params as $key => $val) {
            $arr_temp[]=$key.'='.$val;
        }
        $sign_str = implode('&', $arr_temp);
        $sign_str = sha1($sign_str);
        $res = openssl_get_publickey($this->public_key);
        $check_result = (bool)openssl_verify($sign_str, hex2bin($sign), $res);
        openssl_free_key($res);
        if($check_result){
            return true;
        }else{
            return false;
        }
    }

    //data数据AES解密
    public function aes_decode($tempStr) {
        $tempStr=hex2bin($tempStr);
        $decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$this->open_key,$tempStr,MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    //查询
    public function query($orderId){

    }

    //AES加密
    protected function aes_encode($tempStr) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $tempStr = $this->pkcs5_pad($tempStr, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->open_key, $iv);
        $data = mcrypt_generic($td, $tempStr);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = strtoupper(bin2hex($data));
        return $data;
    }
    //AES加密初码算法
    protected function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    //签名过程
    protected function make_sign($get,$post){
        if(empty($post)){
            $data=$get;
        }else{
            $data=array_merge($get,$post);
        }
        ksort($data);
        $arr_temp = array ();
        foreach ($data as $key => $val) {
            $arr_temp[]=$key.'='.$val;
        }
        $sign_str = implode('&', $arr_temp);
        $sign = sha1($sign_str);
        $res = openssl_pkey_get_private($this->private_key);
        if (openssl_sign($sign, $out, $res)){
            $sign=bin2hex($out);
        }
        openssl_free_key($res);
        return $sign;
    }

    //生成随机字符串
    private function myRand()
    {
        $str = '';
        $rand = mt_rand(32, 38);
        for ($i=0; $i < $rand; $i++) {
            $switch = mt_rand(1, 3);
            switch ($switch) {
                case 1:
                    $str .= mt_rand(0, 9);
                    break;
                case 2:
                    $str .= chr(mt_rand(97, 122));
                    break;
                case 3:
                    $str .= chr(mt_rand(65, 90));
                    break;
                default:
                    $str .= 'none';
                    break;
            }
        }
        return $str;
    }

    //rsa加密
    /*protected function rsa_encode($tempStr){
        $pri_key = openssl_pkey_get_private($this->private_key);
        if($pri_key){
            openssl_private_encrypt($tempStr,$tempStr,$pri_key);#私钥加密
            $tempStr = bin2hex($tempStr);
            openssl_free_key($pri_key);
            return $tempStr;
        }else{
            return false;
        }
    }
    //rsa解密
    protected function rsa_decode($tempStr){
        $pu_key = openssl_pkey_get_public($this->public_key);
        if($pu_key){
            openssl_public_decrypt(hex2bin($tempStr),$tempStr,$pu_key);
            openssl_free_key($pu_key);
            return $tempStr;
        }else{
            return false;
        }
    }*/
    //验签过程
    /*protected function check_sign($array){
        if(empty($array['sign'])){
            return false;
            exit();
        }
        $sign=$array['sign'];#得到返回签名字符串
        unset($array['sign']);#去掉sign节点
        ksort($array);#排序
        $arr_temp = array ();
        foreach ($array as $key => $val) {
            $arr_temp[]=$key.'='.$val;
        }
        $sign_str = implode('&', $arr_temp);
        $sign_str = sha1($sign_str);
        $res = openssl_get_publickey($this->public_key);
        $check_result = (bool)openssl_verify($sign_str, hex2bin($sign), $res);
        openssl_free_key($res);
        if($check_result){
            return true;
        }else{
            return false;
        }
    }*/

}