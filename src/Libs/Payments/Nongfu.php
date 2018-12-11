<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Nongfu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wxpay' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'alipay' => '支付宝扫码',
        ],
        3  => [ //网银
            '01000000' => '邮储银行',
            '04403600' => '徽商银行',
            '01020000' => '工商银行',
            '01030000' => '农业银行',
            '01040000' => '中国银行',
            '01050000' => '建设银行',
            '03010000' => '交通银行',
            '03020000' => '中信银行',
            '03030000' => '光大银行',
            '03040000' => '华夏银行',
            '03050000' => '民生银行',
            '03060000' => '广发银行',
            '03070000' => '深发银行',
            '03080000' => '招商银行',
            '03090000' => '兴业银行',
            '03100000' => '浦发银行',
            '03110000' => '恒丰银行',
            '03134500' => '齐鲁银行',
            '03160000' => '浙商银行',
            '04012900' => '上海银行',
            '04023930' => '厦门银行',
            '04031000' => '北京银行',
            '04044560' => '烟台银行',
            '04053910' => '福州银行',
            '04062410' => '长春银行',
            '04073140' => '镇江银行',
            '04083320' => '宁波银行',
            '04094510' => '济南银行',
            '04100000' => '平安银行',
            '04105840' => '深圳银行',
            '04115010' => '焦作银行',
            '04123330' => '温州银行',
            '04135810' => '广州银行',
            '04145210' => '武汉银行（汉口银行）',
            '04154560' => '恒丰银行',
            '04162640' => '齐齐哈尔银行',
            '04172210' => '沈阳银行',
            '04184930' => '洛阳银行',
            '04192310' => '辽阳银行',
            '04202220' => '大连银行',
            '04213050' => '苏州银行',
            '04221210' => '石家庄银行',
            '04233310' => '杭州银行',
            '04243010' => '南京银行',
            '04256020' => '东莞银行',
            '04263380' => '金华银行',
            '04278810' => '乌鲁木齐银行',
            '04283370' => '绍兴银行',
            '04296510' => '成都银行',
            '04302240' => '抚顺银行',
            '04314730' => '临沂银行',
            '04325250' => '宜昌银行',
            '04332350' => '葫芦岛银行',
            '04341100' => '天津银行',
            '04354910' => '郑州银行',
            '04368710' => '银川银行',
            '04375850' => '珠海银行',
            '04384530' => '淄博银行',
            '04392270' => '锦州银行',
            '04403610' => '合肥银行',
            '04416530' => '重庆银行',
            '04422610' => '哈尔滨银行',
            '04437010' => '贵阳银行',
            '04447910' => '西安银行',
            '04453020' => '无锡银行',
            '04462260' => '丹东银行',
            '04478210' => '兰州银行',
            '04484210' => '南昌银行（江西银行）',
            '04491610' => '太原银行',
            '04504520' => '青岛银行',
            '04512420' => '吉林银行',
            '04523060' => '南通银行',
            '04533120' => '扬州银行',
            '04544240' => '九江银行',
            '04554732' => '日照银行',
            '04562230' => '鞍山银行',
            '04571260' => '秦皇岛银行',
            '04588510' => '西宁银行',
            '04593450' => '台州银行',
            '04603110' => '盐城银行',
            '04615510' => '长沙银行',
            '04624580' => '潍坊银行',
            '04634280' => '赣州银行',
            '04643970' => '泉州银行',
            '04652280' => '营口银行',
            '04667310' => '昆明银行',
            '04672290' => '阜新银行',
            '04683040' => '常州银行',
            '04693080' => '淮安银行',
            '04703350' => '嘉兴银行',
            '04713620' => '芜湖银行',
            '04721460' => '廊坊银行',
            '04733450' => '浙江泰隆商业银行',
            '04741900' => '呼和浩特市城银行',
            '04741910' => '呼和浩特市城银行',
            '04753360' => '湖州银行',
            '04773650' => '马鞍山银行',
            '04786110' => '南宁银行',
            '04791920' => '包头银行',
            '04803070' => '连云港银行',
            '04814650' => '威海银行',
            '04823660' => '淮北银行',
            '04836560' => '攀枝花银行',
            '04843680' => '安庆银行',
            '04856590' => '绵阳银行',
            '04866570' => '泸州银行',
            '04871620' => '大同银行',
            '04885050' => '三门峡城市信用社',
            '04895910' => '湛江银行',
            '04901380' => '张家口银行',
            '04916170' => '桂林银行',
            '04922690' => '大庆银行',
            '04933123' => '靖江市长江城市信用社',
            '04943030' => '徐州银行',
            '04956140' => '柳州银行',
            '04966730' => '南充银行',
            '05171270' => '邯郸市商业银行',
            '14012900' => '上海农村银行',
            '14023052' => '昆山市农村信用合作社联合社',
            '14033055' => '常熟市农村银行',
            '14045840' => '深圳市农村信用合作社联合社',
            '14055810' => '广州市农村信用合作社联合社',
            '14063317' => '杭州市萧山区农村信用合作社联合社',
            '14075882' => '南海市农村信用合作社联合社',
            '14085883' => '顺德市农村信用合作社联合社',
            '14097310' => '昆明市农村信用合作社联合社',
            '14105210' => '武汉市农村信用合作社联合社',
            '14113030' => '徐州市市郊农村信用合作社联合社',
            '14123022' => '江阴市农村银行',
            '14136530' => '重庆市农村信用合作社联合社',
            '14144500' => '山东省市农村信用社',
            '14144520' => '青岛农村信用社',
            '14156020' => '东莞农村信用合作社联合社',
            '14163056' => '张家港市农村银行',
            '14173930' => '厦门市农村信用合作社联合社',
            '14181000' => '北京农村信用联社',
            '14191100' => '天津市农村信用合作社联合社',
            '14203320' => '宁波鄞州农村合作银行',
            '14215881' => '佛山市三水区农村信用合作社联合社',
            '14226510' => '成都市农村信用合作社联合社',
            '14231440' => '沧州市农村信用合作社联合社',
            '14243000' => '江苏省农村信用合作社联合社',
            '14255890' => '江门市新会农村信用合作社联合社',
            '14265930' => '高要市农村信用合作社联合社',
            '14275880' => '佛山市禅城区农村信用社联合社',
            '14283050' => '江苏吴江农村银行',
            '14293300' => '浙江省农村信用社联合社',
            '14303050' => '江苏东吴农村银行',
            '14315850' => '珠海市农村信用合作社联合社',
            '14326030' => '中山农村信用合作社联合社',
            '14333051' => '江苏太仓农村银行股份有限公司',
            '14341770' => '临汾市尧都区信用合作社联合社',
            '14353041' => '江苏武进农村银行股份有限公司',
            '14367000' => '贵州省农村信用合作作联合社',
            '14373020' => '江苏锡州农村银行股份有限公司',
            '14385500' => '湖南省农村信用社联合社',
            '14394200' => '江西农信联合社',
            '14404900' => '河南省农村信用社联合社',
            '14411200' => '河北省农村信用社联合社',
            '14427900' => '陕西省农村信用社联合社',
            '14436100' => '广西农村信用社联合社',
            '14448800' => '新疆维吾尔自治区农村信用社联合',
            '14452400' => '吉林农信联合社',
            '14468700' => '黄河农村银行',
            '14473600' => '安徽省农村信用社联合社',
            '14486400' => '海南省农村信用社联合社',
            '14498500' => '青海省农村信用社联合社',
            '14505800' => '广东省农村信用社联合社',
            '14511900' => '内蒙古自治区农村信用社联合式',
            '14526500' => '四川省农村信用社联合社',
            '14538200' => '甘肃省农村信用社联合社',
            '14542200' => '辽宁省农村信用社联合社',
            '14551600' => '山西省农村信用社联合社',
            '14561100' => '天津滨海农村银行',
            '14572600' => '黑龙江省农村信用社联合社',
            '14595210' => '武汉农村银行',
            '14603040' => '江南农村银行',
            '15006518' => '大邑交银兴民村镇银行',
            '15015363' => '湖北嘉鱼吴江村镇银行',
            '15024521' => '青岛即墨北农商村镇银行',
            '15025371' => '湖北仙桃北农商村镇银行',
            '15036410' => '海口苏南村镇银行',
            '15036512' => '双流诚民村镇银行',
            '15036753' => '宣汉诚民村镇银行',
            '15044015' => '福建建瓯石狮村镇银行',
            '15055411' => '恩施常农商村镇银行',
            '15055416' => '咸丰常农商村镇银行',
            '15083362' => '浙江长兴联合村镇银行',
            '15103352' => '浙江平湖工银村镇银行',
            '15106919' => '重庆璧山工银村镇银行',
            '15111027' => '北京密云汇丰村镇银行',
            '15115270' => '湖北随州曾都汇丰村镇银行',
            '15116917' => '重庆大足汇丰村镇银行有限责任公司',
            '15123181' => '江苏沭阳东吴村镇银行',
            '15136900' => '重庆农村银行',
            '15142080' => '方大村镇银行',
            '15145840' => '深圳龙岗鼎业村镇银行',
            '15156030' => '中山小榄村镇银行',
            '15173120' => '江苏邗江民泰村镇银行',
            '15183651' => '安徽当涂新华村镇银行',
            '15185810' => '广州番禹新华村镇银行',
            '15194737' => '沂水中银富登村镇银行',
            '15195321' => '京山中银富登村镇银行',
            '15195338' => '蕲春中银富登村镇银行',
            '15201000' => '北京顺义银座村镇银行',
            '15204280' => '江西赣州银座村镇银行',
            '15205840' => '深圳福田银座村镇银行',
            '15211000' => '北京怀柔融兴村镇银行',
            '15215840' => '深圳宝安融兴村镇银行',
            '15265130' => '南阳村镇银行',
            '05426900' => '重庆三峡银行',
            '05591750' => '晋中银行',
            '05803320' => '宁波通商银行',
            '05083000' => '江苏银行',
            '03170000' => '渤海银行',
            '14012900' => '上海农商行',
            '04791920' => '包商银行',
            '04053910' => '福建海峡银行',
            '04053910' => '富滇银行',
            '64895910' => '广东南粤银行',
            '04145210' => '汉口银行（武汉银行）',
            '04484210' => '江西银行（南昌银行）',
            '14023052' => '昆山农村商业银行',
            '14283054' => '吴江农商行',
        ],
        4  => [ //QQ扫码
            'qqpay' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'jdpay' => 'JD扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
        ],
        8  => [ //微信WAP
            'wxpayh5' => '微信 H5',
        ],
        9  => [ //支付宝WAP
        ],
        10 => [ //QQWAP
            'qqpayh5' => 'QQ H5',
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://epay.nongfupay.com/pay';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;

    /**
     * 回调地址
     * @var string
     */
    public $callbackUrl;

    /**
     * 通知地址
     * @var string
     */
    public $notifyUrl;

    /**
     * 签名
     * @var string
     */
    public $sign;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];

    public $payType;

    public $orderNo;

    public $money;

    /**
     * 支付对象
     * @var object
     */
    private static $instance;

    public static function getInstance($conf = [])
    {
        if (!(static::$instance instanceof static )) {
            static::$instance = new static($conf);
        }
        return static::$instance;
    }

    private function __construct($conf)
    {
        if ($conf) {
            $this->parterNo    = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->callbackUrl = $conf['callbackUrl'];
            $this->notifyUrl   = $conf['notifyUrl'];
        }
    }

    private function __clone()
    {
    }

    /**
     * 获取支付类型
     */
    public function getPayType()
    {
        return $this->paymentType;
    }

    /**
     * 签名
     */
    public function signature($payType, $money, $orderId)
    {
        $this->payType = $payType;
        $this->orderNo = $orderId;
        $this->money   = $money;

        $this->params = [
            'merchantId' => $this->parterNo,
            'merOrderId' => $this->orderNo,
            'txnAmt'     => $this->money * 100, // 元转
            'backUrl'    => $this->callbackUrl,
            'frontUrl'   => $this->notifyUrl,
            'subject'    => 'CZ',
            'body'       => 'CZ',
            'userId'     => '',
            'merResv1'   => '',
            'signMethod' => 'MD5',
            'signature'  => '',
        ];

        if (!in_array($this->payType, ['wxpay', 'alipay', 'qqpay', 'jdpay', 'wxpayh5', 'qqpayh5'])) {
            $this->params['gateway'] = 'bank';
            $this->params['bankId']  = $this->payType;
            $this->params['dcType']  = '0';
        } else {
            $this->params['gateway'] = $this->payType;
        }

        $this->params['signature'] = $this->getSignStr($this->params);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $this->params['subject'] = base64_encode($this->params['subject']);
        $this->params['body']    = base64_encode($this->params['body']);

        if (in_array($this->payType, ['wxpay', 'alipay', 'qqpay', 'jdpay', 'wxpayh5', 'qqpayh5'])) {
            $client = new Client();
            $result = $client->request('POST', $this->getwayUrl, [
                'verify'      => false,
                'headers'     => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                ],
                'form_params' => $this->params,
            ]);
            if ($result->getStatusCode() == '200') {
                $returnData = $result->getBody();
                $returnData = json_decode($returnData, true);
                if ($this->verifySign($returnData)) {
                    if ($returnData['success'] == 1) {
                        if (isset($returnData['payLink']) && $returnData['payLink']) {
                            $code_url  = $returnData['payLink'];
                            if (strpos($this->payType, 'h5') !== false) {
                                return $code_url;
                            }
                            $qrcodeUrl = Utils::getQrcode($code_url);
                            return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($code_url);
                        }
                    } else {
                        return json_encode([
                            'error' => $returnData['msg'],
                            'order' => $this->orderNo,
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }
                return json_encode([
                    'code'  => 1,
                    'error' => '验签失败',
                ], JSON_UNESCAPED_UNICODE);
            }
            return json_encode([
                'code'  => 1,
                'error' => '网络错误',
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
            foreach ($this->params as $key => $value) {
                $tmp .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
            $tmp .= '</form>';
            $tmp .= '<script type="text/javascript">';
            $tmp .= 'document.orderForm.submit();';
            $tmp .= '</script>';
            $tmp .= '</body>';
            $tmp .= '</html>';

            return $tmp;
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $signature = $params['signature'];
        unset($params['signature']);
        ksort($params);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return $signature == base64_encode(md5($uri, true));
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        unset($params['signMethod']);
        unset($params['signature']);
        ksort($params);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return base64_encode(md5($uri, true));
    }
}
