<?php

namespace Weiming\Jobs;

use Symfony\Component\DomCrawler\Crawler;
use Weiming\Jobs\BaseJob;

use Weiming\Libs\Payments\Duoduo;

use Weiming\Libs\Payments\Gaotong;

use Weiming\Libs\Payments\Hebao;

use Weiming\Libs\Payments\Huaren;
use Weiming\Libs\Payments\Huida;
use Weiming\Libs\Payments\Jinhaizhe;
use Weiming\Libs\Payments\JinhaizheNew;
use Weiming\Libs\Payments\Jinyang;
use Weiming\Libs\Payments\Leying;
use Weiming\Libs\Payments\Likefu;
use Weiming\Libs\Payments\Qiandai;
use Weiming\Libs\Payments\Renxin;
use Weiming\Libs\Payments\Shanfu;
use Weiming\Libs\Payments\Tianfubao;
use Weiming\Libs\Payments\Wallet;
use Weiming\Libs\Payments\Wofu;
use Weiming\Libs\Payments\Xianxingfu;
use Weiming\Libs\Payments\Xunbao;
use Weiming\Libs\Payments\Pppay;
use Weiming\Libs\Payments\Yafu;
use Weiming\Libs\Payments\YafuNew;
use Weiming\Libs\Payments\Zesheng;
use Weiming\Libs\Payments\ziyoufu;
use Weiming\Libs\Payments\ZiyoufuNew;
use Weiming\Libs\Utils;
use Weiming\Models\Company;
use Weiming\Models\Vendor;

/**
 * 查找可用第三方 Job
 */
class AutoJudgment extends BaseJob
{
    //所有支付方式
    private $with = '{"1":"雅付","2":"闪付","3":"讯宝","4":"乐盈","5":"自由付","6":"沃雷特","7":"金海哲","8":"华仁","9":"荷包","10":"立刻付","11":"多多","12":"金海哲(新)","13":"仁信","14":"天付宝","15":"高通","16":"新雅付","17":"先行付","18":"我付","19":"汇达","20":"泽圣","21":"新自由付","22":"钱袋支付","23":"金阳","59":"Pppay"}';

    //日志路径
    private static function logPath()
    {
        $logpath = __DIR__ . '/../../logs/autoJudgment-' . date('Y-m-d') . '.txt';

        return $logpath;
    }

    public static function load(string $path)
    {
        $data['sign'] = self::myRand();
        file_put_contents(__DIR__ . '/../../config/sign.txt', $data);
        $url      = self::my_host() . '/autoJudgment';
        $postdata = http_build_query($data);

        $result = Utils::sendPost($url, $postdata, 30, true);

        return $result;
    }

    //启动查找,定时任务
    public static function start(string $path)
    {

        if (is_file($path)) {
            $state = file_get_contents($path);
            if ($state === '1') {
                $vender  = Vendor::where('company_id', '=', '1')->get();
                $company = Company::where('no', '00001')->first();

                //微信
                $wechat = self::send($company->wechat_vendor_id, 1);if (!preg_match("/(微信|扫码)/", $wechat)) {
                    $wechat = false;
                }
                //var_dump($wechat);
                if ($wechat === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 1);
                        if (preg_match("/(微信|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':微信支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['wechat_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['wechat_vendor_id' => '0']);
                        }
                    }
                }
                //支付宝
                $alipay = self::send($company->alipay_vendor_id, 2);if (!preg_match("/(支付宝|扫码)/", $alipay)) {
                    $alipay = false;
                }

                if ($alipay === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 2);
                        if (preg_match("/(支付宝|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':支付宝支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['alipay_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['alipay_vendor_id' => '0']);
                        }
                    }
                }
                //银行线上支付
                /*$netbank    = self::send($company->netbank_vendor_id, 3);   if(!preg_match("/<.*?<\/html>/", $netbank)) $netbank = false;
                if ($netbank === false) {
                foreach ($vender as $key => $value) {
                $result = self::send($value->pay_type, 3);
                if (preg_match_all("/银行/", $result) > 1) {
                $type = $value->id;
                file_put_contents(self::logPath(), date('Y-m-d H:i:s').':银行支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                $company->update(['netbank_vendor_id' => $type]);
                break;
                }else{
                $company->update(['netbank_vendor_id' => '0']);
                }
                }
                }*/
                //QQ钱包
                $qq = self::send($company->qq_vendor_id, 4);if (!preg_match("/(QQ钱包|扫码)/", $qq)) {
                    $qq = false;
                }

                if ($qq === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 4);
                        if (preg_match("/(QQ钱包|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':QQ扫码支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['qq_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['qq_vendor_id' => '0']);
                        }
                    }
                }
                //京东钱包
                $jd = self::send($company->jd_vendor_id, 5);if (!preg_match("/(京东钱包|扫码)/", $jd)) {
                    $jd = false;
                }

                if ($jd === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 5);
                        if (preg_match("/(京东钱包|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':京东扫码支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['jd_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['jd_vendor_id' => '0']);
                        }
                    }
                }
                //百度钱包
                $baidu = self::send($company->baidu_vendor_id, 6);if (!preg_match("/(百度钱包|扫码)/", $baidu)) {
                    $baidu = false;
                }

                if ($baidu === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 6);
                        if (preg_match("/(百度钱包|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':百度扫码支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['baidu_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['baidu_vendor_id' => '0']);
                        }
                    }
                }
                //银联扫码
                $union = self::send($company->union_vendor_id, 7);if (!preg_match("/(银联|扫码)/", $union)) {
                    $union = false;
                }

                if ($union === false) {
                    foreach ($vender as $key => $value) {
                        $result = self::send($value->pay_type, 7);
                        if (preg_match("/(银联|扫码)/", $result)) {
                            $type = $value->id;
                            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':银联扫码支付匹配到平台:' . $value->pay_type . PHP_EOL, FILE_APPEND);
                            $company->update(['union_vendor_id' => $type]);
                            break;
                        } else {
                            $company->update(['union_vendor_id' => '0']);
                        }
                    }
                }

                //调用自己，定时运行
                /*sleep(60*30);

                $data['sign'] =self::myRand();
                file_put_contents(__DIR__ . '/../../config/sign.txt', $data);
                $url = self::my_host() . '/autoJudgment';
                $postdata = http_build_query($data);

                $result = Utils::sendPost($url, $postdata, 30 , true);
                file_put_contents(self::logPath(), date('Y-m-d H:i:s').'状态:'.$result.PHP_EOL, FILE_APPEND);*/
                file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':匹配完成!' . PHP_EOL, FILE_APPEND);
                return '匹配完成!';
            } else {
                file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':自动匹配支付平台为关闭状态!' . PHP_EOL, FILE_APPEND);
            }
        } else {
            file_put_contents(self::logPath(), date('Y-m-d H:i:s') . ':没找到标志文件!' . PHP_EOL, FILE_APPEND);
        }

    }

    //匹配发送请求
    private static function send($payType, $type)
    {
        if (!$payType) {
            return false;
        }
        $money   = mt_rand(10, 30) . '.' . mt_rand(10, 99);
        $orderId = date('YmdHis') . mt_rand(1000, 9999);

        $vendor = Vendor::where('pay_type', $payType)->first();
        if ($vendor) {
            $conf['parterNo']    = $vendor->no;
            $conf['parterKey']   = $vendor->key;
            $conf['callbackUrl'] = $vendor->callback_url;
            $conf['notifyUrl']   = $vendor->notify_url;

            //匹配支付平台
            switch ($payType) {
                case '1':
                    $PayObj = Yafu::getInstance($conf);
                    break;
                case '2':
                    $PayObj = Shanfu::getInstance($conf);
                    break;
                case '3':
                    $PayObj = Xunbao::getInstance($conf);
                    break;
                case '4':
                    $PayObj = Leying::getInstance($conf);
                    break;
                case '5':
                    $PayObj = ziyoufu::getInstance($conf);
                    break;
                case '6':
                    $PayObj = Wallet::getInstance($conf);
                    break;
                case '7':
                    $PayObj = Jinhaizhe::getInstance($conf);
                    break;
                case '8':
                    $PayObj = Huaren::getInstance($conf);
                    break;
                case '9':
                    $PayObj = Hebao::getInstance($conf);
                    break;
                case '10':
                    $PayObj = Likefu::getInstance($conf);
                    break;
                case '11':
                    $PayObj = Duoduo::getInstance($conf);
                    break;
                case '12':
                    $PayObj = JinhaizheNew::getInstance($conf);
                    break;
                case '13':
                    $PayObj = Renxin::getInstance($conf);
                    break;
                case '14':
                    $PayObj = Tianfubao::getInstance($conf);
                    break;
                case '15':
                    $PayObj = Gaotong::getInstance($conf);
                    break;
                case '16':
                    $PayObj = YafuNew::getInstance($conf);
                    break;
                case '17':
                    $PayObj = Xianxingfu::getInstance($conf);
                    break;
                case '18':
                    $PayObj = Wofu::getInstance($conf);
                    break;
                case '19':
                    $PayObj = Huida::getInstance($conf);
                    break;
                case '20':
                    $PayObj = Zesheng::getInstance($conf);
                    break;
                //case '21':
                //$PayObj = ZiyoufuNew::getInstance($conf);
                //break;
                case '22':
                    $PayObj = Qiandai::getInstance($conf);
                    break;
                case '23':
                    $PayObj = Jinyang::getInstance($conf);
                    break;
                case '59':
                    $PayObj = Pppay::getInstance($conf);
                    break;

                default:
                    return false;
                    break;
            }

            $payTypes = $PayObj->getPayType();
            if ($type === 3) {
                foreach ($payTypes as $key => $value) {
                    if (!$key) {
                        return false; //continue;
                    }
                    $output = $PayObj->signature($key, $money, $orderId)->payment();

                    if ($output === false) {
                        continue;
                    }
                    //file_put_contents( __DIR__ . '/../../logs/bank-' . date('Y-m-d').'.txt', date('Y-m-d H:i:s') . "匹配{$type};---{$output}---平台{$payType}" . PHP_EOL, FILE_APPEND);
                    if (preg_match("/<\/form>/", $output)) {
                        $res = self::sendForm($output);
                        file_put_contents(self::logPath(), date('Y-m-d H:i:s') . "匹配{$type};---bankform{$res}---平台{$payType}" . PHP_EOL, FILE_APPEND);
                        if (preg_match_all("/银行/", $res) > 1) {
                            return $res;
                            break;
                        } else {
                            continue;
                        }
                    } elseif (preg_match("/^(http|https):\/\/.*/", $output)) {
                        $res = self::my_curl($output);
                        file_put_contents(self::logPath(), date('Y-m-d H:i:s') . "匹配{$type};---bankhttp{$res}---平台{$payType}" . PHP_EOL, FILE_APPEND);
                        if (preg_match("/<\/html>$/", $res)) {
                            return $res;
                            break;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
                return false;
            } else {
                $payType_ = key($payTypes[$type]);
                if (!$payType_) {
                    return false;
                }
                $output = $PayObj->signature($payType_, $money, $orderId)->payment();

                if ($output === false) {
                    return false;
                }
                if (preg_match("/^(http|https):\/\/.*$/", $output)) {
                    $res = self::my_curl($output);
                    file_put_contents(self::logPath(), date('Y-m-d H:i:s') . "匹配{$type};---http{$res}---平台{$payType}" . PHP_EOL, FILE_APPEND);
                    return $res;
                } elseif (preg_match("/<\/form>/", $output)) {
                    $res = self::sendForm($output);
                    file_put_contents(self::logPath(), date('Y-m-d H:i:s') . "匹配{$type};---form{$res}---平台{$payType}" . PHP_EOL, FILE_APPEND);
                    return $res;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

    }

    private static function sendForm($form)
    {
        $crawler = new Crawler;
        $tmpArr  = [];
        $crawler->addHtmlContent($form);
        $action = $crawler->filterXPath('//form')->attr('action');
        $method = $crawler->filterXPath('//form')->attr('method');
        /*if($method == 'get')
        {
        $result = self::my_curl($action);
        }else*/
        //{
        $data = [];
        $crawler->filterXPath('//input[@type="hidden"]')->each(function (Crawler $node, $i) use (&$data) {
            $name        = $node->attr('name');
            $value       = $node->attr('value');
            $data[$name] = $value;
        });
        $result = Utils::sendPost($action, $data, 120, true);
        //}
        $dataInfo = urldecode(http_build_query($data));
        file_put_contents(__DIR__ . '/../../logs/bankIfo-' . date('Y-m-d') . '.txt', date('Y-m-d H:i:s') . "{$action};---{$method}---{$dataInfo}---result{$result}" . PHP_EOL, FILE_APPEND);
        return $result;
    }

    //获取host
    private static function my_host()
    {
        $host = $_SERVER["HTTP_HOST"] ?? $_SERVER["SERVER_NAME"];
        if (empty($host)) {
            $httpPath = preg_replace('/http:\/\/(.*)\//', 'http://${1}', $_SERVER["HTTP_REFERER"]);
        } else {
            $httpPath = 'http://' . $host;
        }
        $httpPath = rtrim($httpPath, '/');

        return $httpPath;
    }

    private static function my_curl($url)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); //返回原生内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //数据按字符串返回,不直接输出
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //设置跟踪页面的跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, true); //设置跳转页面的referer
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_error($ch);
        }
        return $result;
    }

    //生成随机字符串
    private static function myRand()
    {
        $str  = '';
        $rand = mt_rand(124, 256);
        for ($i = 0; $i < $rand; $i++) {
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

    //判断HTML
    private static function formatHtml($str)
    {
        return preg_replace("~>\\s+<~", "><", preg_replace("~>\\s+~", ">", preg_replace("~\\s+<~", "<", $str)));
    }

    //判断json数据
    private static function isJSON($str)
    {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
